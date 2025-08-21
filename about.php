<?php
include_once 'config.php';

function getCurrentUser($conn) {
    if (!isLoggedIn()) {
        return null;
    }
    
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        return $result->fetch_assoc();
    }
    
    return null;
}

function getUserNotifications($conn) {
    if (!isLoggedIn()) {
        return ['orders' => 0, 'bookings' => 0, 'messages' => 0];
    }
    
    $user_id = $_SESSION['user_id'];
    
    // Get order notifications
    $orders_count = 0;
    $orders_stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ? AND status IN ('pending', 'processing')");
    if ($orders_stmt) {
        $orders_stmt->bind_param("i", $user_id);
        $orders_stmt->execute();
        $orders_result = $orders_stmt->get_result();
        if ($orders_result) {
            $orders_count = $orders_result->fetch_assoc()['count'];
        }
        $orders_stmt->close();
    }
    
    // Get booking notifications (using test_drive_bookings table)
    $bookings_count = 0;
    $bookings_stmt = $conn->prepare("SELECT COUNT(*) as count FROM test_drive_bookings WHERE user_id = ? AND status = 'pending'");
    if ($bookings_stmt) {
        $bookings_stmt->bind_param("i", $user_id);
        $bookings_stmt->execute();
        $bookings_result = $bookings_stmt->get_result();
        if ($bookings_result) {
            $bookings_count = $bookings_result->fetch_assoc()['count'];
        }
        $bookings_stmt->close();
    }
    
    // Messages count (no messages table in your DB, so return 0)
    $messages_count = 0;
    
    return [
        'orders' => $orders_count,
        'bookings' => $bookings_count,
        'messages' => $messages_count
    ];
}

function logUserAction($conn, $action, $details = '') {
    // Since there's no user_activity_log table in your database, 
    // we'll just return true for now. You can create the table later if needed.
    return true;
}

// ==================== MAIN PAGE LOGIC ====================

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
    $notifications = getUserNotifications($conn);
}

// Handle callback request form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'callback_request') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (!empty($name) && !empty($phone)) {
        // Insert into callback_requests table (matching your DB schema)
        $email = $user_data ? $user_data['email'] : '';
        $service = 'General Inquiry'; // Default service
        
        $stmt = $conn->prepare("INSERT INTO callback_requests (name, email, phone, service, message, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssss", $name, $email, $phone, $service, $message);
        
        if ($stmt->execute()) {
            if ($user_data) {
                logUserAction($conn, 'callback_requested', "Name: $name, Phone: $phone");
            }
            echo json_encode(['success' => true, 'message' => 'Callback request submitted successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to submit request. Please try again.']);
        }
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
        exit;
    }
}

// Get testimonials (matching your DB schema)
$testimonials_query = "SELECT * FROM testimonials WHERE is_active = 1 ORDER BY created_at DESC LIMIT 6";
$testimonials_result = $conn->query($testimonials_query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="description" content="AUTOBUBA - Premium Car Marketplace">
    <meta name="keywords" content="cars, marketplace, automotive, buy, rent">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="icon" href="img/autobuba logo.png" type="image/png">
    <title data-translate="About Us">AUTOBUBA - About Us</title>

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Flag Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/3.5.0/css/flag-icon.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="loading.css">
    <link rel="stylesheet" href="css/cart.css">

    <style>
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

        /* Language dropdown styles */
        .language-dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            z-index: 1000;
            min-width: 10rem;
            padding: 0.5rem 0;
            margin: 0.125rem 0 0;
            font-size: 1rem;
            color: #212529;
            text-align: left;
            list-style: none;
            background-color: #fff;
            background-clip: padding-box;
            border: 1px solid rgba(0,0,0,.15);
            border-radius: 0.25rem;
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,.175);
        }

        .dropdown-menu.show {
            display: block;
        }

        .dropdown-item {
            display: block;
            width: 100%;
            padding: 0.25rem 1.5rem;
            clear: both;
            font-weight: 400;
            color: #212529;
            text-align: inherit;
            white-space: nowrap;
            background-color: transparent;
            border: 0;
        }

        .dropdown-item:hover, .dropdown-item:focus {
            color: #16181b;
            text-decoration: none;
            background-color: #f8f9fa;
        }

        .dropdown-item.active, .dropdown-item:active {
            color: #fff;
            text-decoration: none;
            background-color: #d90429;
        }

        .flag-icon {
            margin-right: 8px;
        }

        /* Enhanced header buttons */
        .header-buttons {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .header-buttons .nav-link {
            position: relative;
            padding: 0.5rem;
            border-radius: 50%;
            transition: all 0.3s ease;
            color: #333;
        }

        .header-buttons .nav-link:hover {
            background-color: #f8f9fa;
            transform: translateY(-2px);
        }

        .cart-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            font-size: 0.7rem;
            min-width: 18px;
            height: 18px;
            display: none;
            align-items: center;
            justify-content: center;
            border: 2px solid white;
        }

        .toast-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 5px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 9999;
            animation: slideInRight 0.3s ease;
        }

        .toast-notification.error {
            background: #dc3545;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* Global Styles */
        :root {
            --primary-color: #dc3545;
            --secondary-color: #2d3436;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --gray-color: #6c757d;
            --success-color: #28a745;
            --info-color: #17a2b8;
            --white-color: #ffffff;
            --shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Poppins', sans-serif;
            color: var(--dark-color);
            background-color: #f5f5f5;
            height: fit-content;
        }

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
            max-height: 50px;
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

        /* About Page Specific Styles */
        .about-hero {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 100px 0;
            text-align: center;
        }

        .about-section {
            padding: 80px 0;
        }

        .feature-card {
            background: white;
            border-radius: 15px;
            padding: 40px 30px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            height: 100%;
        }

        .feature-card:hover {
            transform: translateY(-10px);
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 30px;
        }

        .stats-section {
            background: #f8f9fa;
            padding: 80px 0;
        }

        .stat-card {
            text-align: center;
            padding: 30px;
        }

        .stat-number {
            font-size: 48px;
            font-weight: bold;
            color: #dc3545;
            margin-bottom: 10px;
        }

        .callback-section {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 80px 0;
        }

        .callback-form {
            background: white;
            border-radius: 15px;
            padding: 40px;
            color: #333;
        }

        .testimonial-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            height: 100%;
        }

        .testimonial-rating {
            color: #ffc107;
            margin-bottom: 15px;
        }

        .phone-modal .modal-content {
            border-radius: 15px;
            border: none;
        }

        .phone-modal .modal-header {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            border-radius: 15px 15px 0 0;
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

        /* Responsive Styles */
        @media (max-width: 991px) {
            .breadcrumb-option {
                padding: 80px 0;
            }

            .breadcrumb__text h2 {
                font-size: 36px;
            }
        }

        @media (max-width: 768px) {
            .breadcrumb-option {
                padding: 60px 0;
            }

            .breadcrumb__text h2 {
                font-size: 30px;
            }

            .user-dropdown .dropdown-menu {
                min-width: 280px;
                margin-right: 1rem;
            }
            
            .header-buttons {
                gap: 0.5rem;
            }

            .about-section {
                padding: 60px 0;
            }

            .feature-card {
                padding: 30px 20px;
            }

            .stat-number {
                font-size: 36px;
            }

            .callback-form {
                padding: 30px;
            }
        }

        @media (max-width: 575px) {
            .breadcrumb-option {
                padding: 50px 0;
            }

            .breadcrumb__text h2 {
                font-size: 24px;
            }

            .about-section {
                padding: 40px 0;
            }

            .feature-card {
                padding: 25px 15px;
            }

            .callback-form {
                padding: 20px;
            }
        }
        /* Target the image container in the about section */
.about-section .col-lg-6:last-child {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 400px; /* Ensures minimum height for proper centering */
}

/* Style the image itself */
.about-section .col-lg-6:last-child img {
    max-height: 400px;
    width: auto; /* Changed from 50% to auto for better proportions */
    object-fit: contain; /* Changed from cover to contain to show full image */
    transition: transform 0.3s ease;
}

/* Add hover effect */
.about-section .col-lg-6:last-child img:hover {
    transform: scale(1.05);
}

/* Responsive adjustments */
@media (max-width: 991px) {
    .about-section .col-lg-6:last-child {
        min-height: 300px;
        margin-top: 30px;
    }
    
    .about-section .col-lg-6:last-child img {
        max-height: 300px;
    }
}

@media (max-width: 768px) {
    .about-section .col-lg-6:last-child {
        min-height: 250px;
        margin-top: 20px;
    }
    
    .about-section .col-lg-6:last-child img {
        max-height: 250px;
        width: 80%; /* Slightly larger on mobile */
    }
}

/* Alternative approach using flexbox for the entire row */
.about-section .row.align-items-center {
    min-height: 500px;
}

.about-section .row.align-items-center .col-lg-6 {
    display: flex;
    flex-direction: column;
    justify-content: center;
}
    </style>
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
                            <a class="nav-link text-black text-decoration-none mx-4 underline" href="about.php"
                                data-translate="About">About</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-black text-decoration-none mx-4" href="work.php"
                                data-translate="Work">Work</a>
                        </li>
                    </ul>

                    <!-- Language Dropdown -->
                    <div class="language-dropdown mr-4">
                        <div class="dropdown">
                            <button class="btn btn-outline-danger dropdown-toggle" type="button" id="languageDropdown" 
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="flag-icon flag-icon-gb"></span>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="languageDropdown">
                                <li>
                                    <a class="dropdown-item language-option active" href="#" data-lang="en">
                                        <span class="flag-icon flag-icon-gb"></span>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item language-option" href="#" data-lang="sq">
                                        <span class="flag-icon flag-icon-al"></span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="header-buttons">
                        <a href="book-test-drive.php" class="nav-link ml-3" data-bs-toggle="tooltip" title="Book Test Drive">
                            <i class="fa-solid fa-car-side"></i>
                        </a>
                        
                        <!-- Cart Button -->
                        <a id="cart" href="#" class="nav-link position-relative" data-bs-toggle="tooltip" title="Shopping Cart">
                            <i class="fa-solid fa-cart-shopping"></i>
                            <span class="cart-badge" id="cartBadge">0</span>
                        </a>
                        
                        <a href="report.php" class="nav-link" data-bs-toggle="tooltip" title="Report a problem">
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
    <div class="breadcrumb-option" style="background-image: url('img/breadcrumb-about_us.jpg');">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 text-center">
                    <div class="breadcrumb__text">
                        <h2 data-translate="About AUTOBUBA">About AUTOBUBA</h2>
                        <div class="breadcrumb__links">
                            <a href="./index.php"><i class="fa fa-home"></i> <span
                                    data-translate="Home">Home</span></a>
                            <span data-translate="About">About</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Breadcrumb End -->

    <!-- About Section -->
    <section class="about-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h2 class="mb-4" data-translate="our_story_title">Our Story</h2>
                    <p class="mb-4" data-translate="our_story_text">Founded recently in 2025, AUTOBUBA is leading to be one of the most trusted car dealers in the region. Our commitment to quality, transparency, and customer satisfaction has made us the preferred choice for thousands of satisfied customers.</p>
                    <p data-translate="our_mission_text">We believe that buying a car should be an exciting and stress-free experience. That's why we've built our business on the principles of honesty, integrity, and exceptional customer service.</p>
                </div>
                <div class="col-lg-6">
                    <img src="img/logo zoom.png" alt="AUTOBUBA Showroom" class="img-fluid">
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="about-section bg-light">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center mb-5">
                    <h2 class="mb-4" data-translate="why_choose_title">Why Choose AUTOBUBA?</h2>
                    <p class="lead" data-translate="why_choose_subtitle">We offer more than just cars - we provide a complete automotive experience.</p>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fa-solid fa-shield-halved"></i>
                        </div>
                        <h4 class="mb-3" data-translate="quality_assurance">Quality Assurance</h4>
                        <p data-translate="quality_assurance_text">Every vehicle undergoes rigorous inspection to ensure the highest quality standards.</p>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fa-solid fa-handshake"></i>
                        </div>
                        <h4 class="mb-3" data-translate="trusted_service">Trusted Service</h4>
                        <p data-translate="trusted_service_text">Over a decade of experience serving customers with honesty and transparency.</p>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fa-solid fa-tools"></i>
                        </div>
                        <h4 class="mb-3" data-translate="after_sales">After-Sales Support</h4>
                        <p data-translate="after_sales_text">Comprehensive maintenance and support services for all our customers.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-number">5000+</div>
                        <h5 data-translate="happy_customers">Happy Customers</h5>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-number">500+</div>
                        <h5 data-translate="cars_sold">Cars Sold Monthly</h5>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-number">15+</div>
                        <h5 data-translate="years_experience">Years Experience</h5>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-number">98%</div>
                        <h5 data-translate="satisfaction_rate">Satisfaction Rate</h5>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="about-section" id="testimonies">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center mb-5">
                    <h2 class="mb-4" data-translate="customer_testimonials">What Our Customers Say</h2>
                    <p class="lead" data-translate="testimonials_subtitle">Don't just take our word for it - hear from our satisfied customers.</p>
                </div>
            </div>
            <div class="row">
                <?php if ($testimonials_result && $testimonials_result->num_rows > 0): ?>
                    <?php while ($testimonial = $testimonials_result->fetch_assoc()): ?>
                        <div class="col-lg-4 mb-4">
                            <div class="testimonial-card">
                                <div class="testimonial-rating">
                                    <?php for ($i = 0; $i < $testimonial['rating']; $i++): ?>
                                        <i class="fa-solid fa-star"></i>
                                    <?php endfor; ?>
                                </div>
                                <p class="mb-3">"<?= htmlspecialchars($testimonial['message']) ?>"</p>
                                <div class="d-flex align-items-center">
                                    <div class="user-avatar me-3" style="width: 40px; height: 40px; background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                        <?= strtoupper(substr($testimonial['name'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <h6 class="mb-0"><?= htmlspecialchars($testimonial['name']) ?></h6>
                                        <?php if ($testimonial['position'] && $testimonial['company']): ?>
                                            <small class="text-muted"><?= htmlspecialchars($testimonial['position']) ?> at <?= htmlspecialchars($testimonial['company']) ?></small>
                                        <?php else: ?>
                                            <small class="text-muted"><?= date('M Y', strtotime($testimonial['created_at'])) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Callback Request Section -->
    <section class="callback-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-6">
                    <h2 class="mb-4" style="color:white;" data-translate="callback_title">Request a Callback</h2>
                    <p class="mb-4" style="color:white;" data-translate="callback_subtitle">Have questions? Our team is here to help. Request a callback and we'll get in touch with you shortly.</p>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="fa-solid fa-check me-2"></i> <span data-translate="callback_benefit_1">Expert advice on car selection</span></li>
                        <li class="mb-2"><i class="fa-solid fa-check me-2"></i> <span data-translate="callback_benefit_2">Financing options discussion</span></li>
                        <li class="mb-2"><i class="fa-solid fa-check me-2"></i> <span data-translate="callback_benefit_3">Trade-in evaluation</span></li>
                        <li class="mb-2"><i class="fa-solid fa-check me-2"></i> <span data-translate="callback_benefit_4">Personalized recommendations</span></li>
                    </ul>
                </div>
                <div class="col-lg-6">
                    <div class="callback-form">
                        <form id="callbackForm">
                            <div class="mb-3">
                                <label for="name" class="form-label" data-translate="name_label">Full Name *</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label" data-translate="phone_label">Phone Number *</label>
                                <input type="tel" class="form-control" id="phone" name="phone" required>
                            </div>
                            <div class="mb-3">
                                <label for="message" class="form-label" data-translate="message_label">Message (Optional)</label>
                                <textarea class="form-control" id="message" name="message" rows="4" placeholder="Tell us how we can help you..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-danger w-100" data-translate="request_callback">Request Callback</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

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
            <h2 class="modal-title">Contact Information</h2>
            <p>Please call us at:</p>
            <div class="modal-number-container">
                <a href="tel:+69 69 690 6969" class="modal-number" id="phoneNumber">+355 69 311 3209</a>
                <button class="modal-copy-button" id="modalCopyButton">
                    <svg class="modal-copy-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path
                            d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z" />
                    </svg>
                </button>
                <span class="copy-tooltip" id="modalTooltip">Copied to clipboard!</span>
            </div>
            <button class="close-button" id="closeModal">Close</button>
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
            <div class="empty-cart">
                <i class="fa fa-shopping-cart"></i>
                <p data-translate="Your cart is empty">Your cart is empty</p>
            </div>
        </div>
    </div>

    <!-- Js Plugins -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
    <script src="loading.js"></script>

    <!-- Complete Cart JavaScript -->
    <script>
    let cart = JSON.parse(localStorage.getItem('autobuba_cart')) || [];

    // Clean up any corrupted cart items
    cart = cart.filter(item => {
        return item && 
               item.id && 
               item.name && 
               item.name !== 'Unknown Vehicle' && 
               item.price && 
               !isNaN(parseFloat(item.price));
    });

    // Save the cleaned cart back
    localStorage.setItem('autobuba_cart', JSON.stringify(cart));

    document.addEventListener('DOMContentLoaded', function() {
        updateCartCounter();
        
        const cartButton = document.getElementById('cart');
        if (cartButton) {
            cartButton.addEventListener('click', function(e) {
                e.preventDefault();
                showCartSidebar();
            });
        }
    });

    function updateCartCounter() {
        const cartBadge = document.getElementById('cartBadge');
        const cartCount = cart ? cart.length : 0;
        
        if (cartBadge) {
            if (cartCount > 0) {
                cartBadge.textContent = cartCount;
                cartBadge.style.display = 'flex';
            } else {
                cartBadge.style.display = 'none';
            }
        }
    }

    function showCartSidebar() {
        const cartSidebar = document.getElementById('cartCars');
        const cartBody = document.getElementById('selectedCars');
        
        if (!cartSidebar || !cartBody) {
            console.error('Cart elements not found');
            return;
        }
        
        cartBody.innerHTML = '';
        
        if (!cart || cart.length === 0) {
            cartBody.innerHTML = `
                <div class="empty-cart">
                    <i class="fa fa-shopping-cart"></i>
                    <p data-translate="Your cart is empty">Your cart is empty</p>
                </div>
            `;
        } else {
            const cartItemsHTML = cart.map(item => {
                const itemId = item.id || '';
                const itemName = item.name || 'Unknown Vehicle';
                const itemPrice = parseFloat(item.price) || 0;
                const itemImg = item.img || 'img/cars/default.jpg';
                
                return `
                    <div class="selected-car-item" data-id="${itemId}">
                        <div class="selected-car-img">
                            <img src="${itemImg}" alt="${itemName}" onerror="this.src='img/cars/default.jpg'">
                        </div>
                        <div class="selected-car-info">
                            <div class="selected-car-name">${itemName}</div>
                            <div class="selected-car-price">$${itemPrice.toLocaleString()}</div>
                        </div>
                        <button class="selected-car-remove" onclick="removeFromCart('${itemId}')" title="Remove from cart">
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                `;
            }).join('');
            
            cartBody.innerHTML = cartItemsHTML;
        }
        
        cartSidebar.style.display = 'flex';
        
        setTimeout(() => {
            cartSidebar.classList.add('show');
        }, 10);
    }

    function removeFromCart(vehicleId) {
        if (!vehicleId) {
            showToast('error', 'Invalid item ID');
            return;
        }
        
        const originalLength = cart.length;
        cart = cart.filter(item => item && item.id !== vehicleId);
        
        if (cart.length < originalLength) {
            localStorage.setItem('autobuba_cart', JSON.stringify(cart));
            updateCartCounter();
            showCartSidebar();
            showToast('success', 'Item removed from cart!');
        } else {
            showToast('error', 'Item not found in cart');
        }
    }

    function closeCart() {
        const cartSidebar = document.getElementById('cartCars');
        
        if (cartSidebar) {
            cartSidebar.classList.remove('show');
        }
        
        setTimeout(() => {
            if (cartSidebar) {
                cartSidebar.style.display = 'none';
            }
        }, 300);
    }

    document.addEventListener('click', function(e) {
        if (e.target && (e.target.id === 'closeCartdisplay' || e.target.closest('#closeCartdisplay'))) {
            closeCart();
        }
    });

    function showToast(type, message) {
        const existingToasts = document.querySelectorAll('.toast-notification');
        existingToasts.forEach(toast => toast.remove());
        
        const toast = document.createElement('div');
        toast.className = `toast-notification ${type}`;
        toast.innerHTML = `
            <i class="fa fa-${type === 'success' ? 'check' : 'exclamation'}-circle me-2"></i>
            ${message || 'Action completed'}
        `;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            if (toast && toast.parentNode) {
                toast.remove();
            }
        }, 3000);
    }
    </script>

    <!-- Callback Form Script -->
    <script>
    document.getElementById('callbackForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'callback_request');
        
        fetch('about.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('success', data.message);
                this.reset();
            } else {
                showToast('error', data.message);
            }
        })
        .catch(error => {
            showToast('error', 'An error occurred. Please try again.');
        });
    });
    </script>

    <!-- Phone button functionality -->
    <script>
        const phoneLogo = document.getElementById('phoneLogo');
        const contactModal = document.getElementById('contactModal');
        const closeModal = document.getElementById('closeModal');
        const modalCopyButton = document.getElementById('modalCopyButton');
        const modalTooltip = document.getElementById('modalTooltip');

        if (phoneLogo && contactModal && closeModal) {
            // Open modal
            phoneLogo.addEventListener('click', function () {
                contactModal.style.display = 'flex';
            });

            // Close modal
            closeModal.addEventListener('click', function () {
                contactModal.style.display = 'none';
            });

            // Close modal when clicking outside
            window.addEventListener('click', function (e) {
                if (e.target === contactModal) {
                    contactModal.style.display = 'none';
                }
            });

            // Copy phone number
            if (modalCopyButton && modalTooltip) {
                modalCopyButton.addEventListener('click', function () {
                    const phoneNumber = document.getElementById('phoneNumber').textContent;
                    navigator.clipboard.writeText(phoneNumber);

                    modalTooltip.style.opacity = '1';
                    setTimeout(function () {
                        modalTooltip.style.opacity = '0';
                    }, 2000);
                });
            }
        }
    </script>

    <!-- Loading screen functionality -->
    <script>
        window.addEventListener('load', function () {
            setTimeout(function () {
                const loadingScreen = document.getElementById('loading-screen');
                if (loadingScreen) {
                    loadingScreen.style.opacity = '0';
                    setTimeout(function () {
                        loadingScreen.style.display = 'none';
                    }, 500);
                }
            }, 1500);
        });
    </script>

    <!-- Language Translation Script -->
    <script>
    // Translation data
    const translations = {
      en: {
        // Header
        Cars: "Cars",
        Blog: "Blog",
        About: "About",
        Work: "Work",
        "Log In": "Log In",

        // About Page
        "About AUTOBUBA": "About AUTOBUBA",
        Home: "Home",
        our_story_title: "Our Story",
        our_story_text: "Founded recently in 2025, AUTOBUBA is leading to be one of the most trusted car dealers in the region. Our commitment to quality, transparency, and customer satisfaction has made us the preferred choice for thousands of satisfied customers.",
        our_mission_text: "We believe that buying a car should be an exciting and stress-free experience. That's why we've built our business on the principles of honesty, integrity, and exceptional customer service.",
        why_choose_title: "Why Choose AUTOBUBA?",
        why_choose_subtitle: "We offer more than just cars - we provide a complete automotive experience.",
        quality_assurance: "Quality Assurance",
        quality_assurance_text: "Every vehicle undergoes rigorous inspection to ensure the highest quality standards.",
        trusted_service: "Trusted Service",
        trusted_service_text: "Over a decade of experience serving customers with honesty and transparency.",
        after_sales: "After-Sales Support",
        after_sales_text: "Comprehensive maintenance and support services for all our customers.",
        happy_customers: "Happy Customers",
        cars_sold: "Cars Sold Monthly",
        years_experience: "Years Experience",
        satisfaction_rate: "Satisfaction Rate",
        customer_testimonials: "What Our Customers Say",
        testimonials_subtitle: "Don't just take our word for it - hear from our satisfied customers.",
        callback_title: "Request a Callback",
        callback_subtitle: "Have questions? Our team is here to help. Request a callback and we'll get in touch with you shortly.",
        callback_benefit_1: "Expert advice on car selection",
        callback_benefit_2: "Financing options discussion",
        callback_benefit_3: "Trade-in evaluation",
        callback_benefit_4: "Personalized recommendations",
        name_label: "Full Name *",
        phone_label: "Phone Number *",
        message_label: "Message (Optional)",
        request_callback: "Request Callback",

        // Footer
        Navigation: "Navigation",
        "Contact Us": "Contact Us",
        Learn: "Learn",
        "Why Us?": "Why Us?",
        "Who are we?": "Who are we?",
        Hatchback: "Hatchback",
        Sedan: "Sedan",
        SUV: "SUV",
        "Copyright 2025 AUTOBUBA. All Rights Reserved.": "© Copyright 2025 QUOLYTECH. All Rights Reserved.",

        // Cart
        "Your Cart": "Your Cart",
        "Your cart is empty": "Your cart is empty",
      },
      sq: {
        // Header
        Cars: "Makinat",
        Blog: "Blog",
        About: "Rreth Nesh",
        Work: "Puna",
        "Log In": "Hyr",

        // About Page
        "About AUTOBUBA": "Rreth AUTOBUBA",
        Home: "Kryefaqja",
        our_story_title: "Historia Jonë",
        our_story_text: "E themeluar në 2025, AUTOBUBA synon të jetë një nga dealerët më të besuar të makinave në rajon. Angazhimi ynë ndaj cilësisë, transparencës dhe kënaqësisë së klientëve na ka bërë zgjedhjen e preferuar për mijëra klientë të kënaqur.",
        our_mission_text: "Ne besojmë se blerja e një makine duhet të jetë një përvojë emocionuese dhe pa stres. Prandaj kemi ndërtuar biznesin tonë mbi parimet e ndershmërisë, integritetit dhe shërbimit të jashtëzakonshëm të klientëve.",
        why_choose_title: "Pse të Zgjidhni AUTOBUBA?",
        why_choose_subtitle: "Ne ofrojmë më shumë se thjesht makina - ne sigurojmë një përvojë të plotë automobilistike.",
        quality_assurance: "Sigurimi i Cilësisë",
        quality_assurance_text: "Çdo automjet kalon kontrolle të rreptë për të siguruar standardet më të larta të cilësisë.",
        trusted_service: "Shërbim i Besuar",
        trusted_service_text: "Mbi një dekadë përvojë në shërbimin e klientëve me ndershmëri dhe transparencë.",
        after_sales: "Mbështetje Pas Shitjes",
        after_sales_text: "Shërbime gjithëpërfshirëse mirëmbajtjeje dhe mbështetjeje për të gjithë klientët tanë.",
        why_choose_title: "Pse të Zgjidhni AUTOBUBA?",
        why_choose_subtitle: "Ne ofrojmë më shumë se thjesht makina - ne sigurojmë një përvojë të plotë automobilistike.",
        quality_assurance: "Sigurimi i Cilësisë",
        quality_assurance_text: "Çdo automjet kalon kontrolle të rreptë për të siguruar standardet më të larta të cilësisë.",
        trusted_service: "Shërbim i Besuar",
        trusted_service_text: "Mbi një dekadë përvojë në shërbimin e klientëve me ndershmëri dhe transparencë.",
        after_sales: "Mbështetje Pas Shitjes",
        after_sales_text: "Shërbime gjithëpërfshirëse mirëmbajtjeje dhe mbështetjeje për të gjithë klientët tanë.",
        happy_customers: "Klientë të Lumtur",
        cars_sold: "Makina të Shitura Mujore",
        years_experience: "Vite Përvojë",
        satisfaction_rate: "Shkalla e Kënaqësisë",
        customer_testimonials: "Çfarë Thonë Klientët Tanë",
        testimonials_subtitle: "Mos merrni vetëm fjalën tonë - dëgjoni nga klientët tanë të kënaqur.",
        callback_title: "Kërkoni një Telefonatë",
        callback_subtitle: "Keni pyetje? Ekipi ynë është këtu për të ndihmuar. Kërkoni një telefonatë dhe ne do të kontaktojmë me ju së shpejti.",
        callback_benefit_1: "Këshilla eksperte për zgjedhjen e makinës",
        callback_benefit_2: "Diskutim i opsioneve të financimit",
        callback_benefit_3: "Vlerësim i shkëmbimit",
        callback_benefit_4: "Rekomandime të personalizuara",
        name_label: "Emri i Plotë *",
        phone_label: "Numri i Telefonit *",
        message_label: "Mesazhi (Opsional)",
        request_callback: "Kërkoni Telefonatë",

        // Footer
        Navigation: "Navigimi",
        "Contact Us": "Na Kontaktoni",
        Learn: "Mësoni",
        "Why Us?": "Pse Ne?",
        "Who are we?": "Kush jemi ne?",
        Hatchback: "Hatchback",
        Sedan: "Sedan",
        SUV: "SUV",
        "Copyright 2025 AUTOBUBA. All Rights Reserved.": "© E drejta e autorit 2025 QUOLYTECH. Të gjitha të drejtat e rezervuara.",

        // Cart
        "Your Cart": "Shporta Juaj",
        "Your cart is empty": "Shporta juaj është bosh",
      },
    }

    // Language Manager Class
    class LanguageManager {
      constructor() {
        this.currentLanguage = this.getStoredLanguage() || "en"
        this.init()
      }

      init() {
        this.setupEventListeners()
        this.updateLanguageDisplay()
        this.translatePage()
      }

      getStoredLanguage() {
        return localStorage.getItem("autobuba_language")
      }

      setStoredLanguage(lang) {
        localStorage.setItem("autobuba_language", lang)
      }

      setupEventListeners() {
        // Language dropdown options
        const languageOptions = document.querySelectorAll(".language-option")
        languageOptions.forEach((option) => {
          option.addEventListener("click", (e) => {
            e.preventDefault()
            const selectedLang = option.getAttribute("data-lang")
            this.changeLanguage(selectedLang)
          })
        })

        // Also handle direct dropdown button clicks
        const dropdownButton = document.getElementById("languageDropdown")
        if (dropdownButton) {
          dropdownButton.addEventListener("click", () => {
            this.updateLanguageDisplay()
          })
        }
      }

      changeLanguage(lang) {
        if (translations[lang]) {
          this.currentLanguage = lang
          this.setStoredLanguage(lang)
          this.updateLanguageDisplay()
          this.translatePage()
          this.showLanguageChangeNotification(lang)
        }
      }

      updateLanguageDisplay() {
        // Update dropdown button
        const dropdownButton = document.getElementById("languageDropdown")
        const languageOptions = document.querySelectorAll(".language-option")

        if (dropdownButton) {
          // Update flag in dropdown button - only show flag, no text
          const flagClass = this.currentLanguage === "en" ? "flag-icon-gb" : "flag-icon-al"
          dropdownButton.innerHTML = `<span class="flag-icon ${flagClass}"></span>`
        }

        // Update active state in dropdown options
        languageOptions.forEach((option) => {
          const optionLang = option.getAttribute("data-lang")
          if (optionLang === this.currentLanguage) {
            option.classList.add("active")
          } else {
            option.classList.remove("active")
          }
        })
      }

      translatePage() {
        const elementsToTranslate = document.querySelectorAll("[data-translate]")
        const currentTranslations = translations[this.currentLanguage]

        elementsToTranslate.forEach((element) => {
          const key = element.getAttribute("data-translate")
          if (currentTranslations[key]) {
            // Handle different element types
            if (element.tagName === "INPUT" && (element.type === "submit" || element.type === "button")) {
              element.value = currentTranslations[key]
            } else if (element.tagName === "INPUT" && element.placeholder) {
              element.placeholder = currentTranslations[key]
            } else if (element.tagName === "OPTION") {
              element.textContent = currentTranslations[key]
            } else {
              // For regular elements, preserve HTML structure but translate text
              const innerHTML = element.innerHTML
              const hasHTML = innerHTML !== element.textContent

              if (hasHTML) {
                // If element contains HTML, only replace the text nodes
                this.translateElementWithHTML(element, currentTranslations[key])
              } else {
                // Simple text replacement
                element.textContent = currentTranslations[key]
              }
            }
          }
        })

        // Update page title
        if (currentTranslations["About Us"]) {
          document.title = currentTranslations["About Us"] + " - AUTOBUBA"
        }

        // Update HTML lang attribute
        document.documentElement.lang = this.currentLanguage === "en" ? "en" : "sq"
      }

      translateElementWithHTML(element, translation) {
        // For elements that contain HTML (like spans, icons), we need to be more careful
        const childNodes = Array.from(element.childNodes)
        const textNodes = childNodes.filter((node) => node.nodeType === Node.TEXT_NODE)

        if (textNodes.length === 1 && textNodes[0].textContent.trim()) {
          // If there's only one text node, replace it
          textNodes[0].textContent = translation
        } else {
          // For more complex cases, replace the entire content but preserve structure
          const tempDiv = document.createElement("div")
          tempDiv.innerHTML = element.innerHTML

          // Try to find and replace text content
          const walker = document.createTreeWalker(tempDiv, NodeFilter.SHOW_TEXT, null, false)

          let textNode
          while ((textNode = walker.nextNode())) {
            if (textNode.textContent.trim()) {
              textNode.textContent = translation
              break // Replace only the first significant text node
            }
          }

          element.innerHTML = tempDiv.innerHTML
        }
      }

      showLanguageChangeNotification(lang) {
        const langName = lang === "en" ? "English" : "Albanian"
        const message =
          lang === "en"
            ? `Language changed to ${langName}`
            : `Gjuha u ndryshua në ${langName === "English" ? "Anglisht" : "Shqip"}`

        // Remove existing notifications
        const existingNotifications = document.querySelectorAll(".language-notification")
        existingNotifications.forEach((notification) => notification.remove())

        // Create notification
        const notification = document.createElement("div")
        notification.className = "language-notification"
        notification.innerHTML = `
                <i class="fa fa-language me-2"></i>
                ${message}
            `

        // Style the notification
        Object.assign(notification.style, {
          position: "fixed",
          top: "20px",
          right: "20px",
          background: "#28a745",
          color: "white",
          padding: "12px 20px",
          borderRadius: "8px",
          boxShadow: "0 4px 12px rgba(0,0,0,0.15)",
          zIndex: "9999",
          fontSize: "14px",
          fontWeight: "500",
          animation: "slideInRight 0.3s ease",
          maxWidth: "300px",
        })

        document.body.appendChild(notification)

        // Remove notification after 3 seconds
        setTimeout(() => {
          if (notification && notification.parentNode) {
            notification.style.animation = "slideOutRight 0.3s ease"
            setTimeout(() => {
              if (notification && notification.parentNode) {
                notification.remove()
              }
            }, 300)
          }
        }, 3000)
      }

      // Method to get current language
      getCurrentLanguage() {
        return this.currentLanguage
      }

      // Method to get translation for a specific key
      getTranslation(key) {
        return translations[this.currentLanguage][key] || key
      }
    }

    // Initialize language manager when DOM is loaded
    document.addEventListener("DOMContentLoaded", () => {
      window.languageManager = new LanguageManager()
    })

    // Add CSS animations for notifications
    const style = document.createElement("style")
    style.textContent = `
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
        
        .language-notification {
            transition: all 0.3s ease;
        }
    `
    document.head.appendChild(style)
    </script>
</body>
</html>

<?php
include_once 'config.php';

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

// Blog posts data
$blog_posts = [
    [
        'id' => 1,
        'title' => 'Top 10 Electric Vehicles to Watch in 2025',
        'slug' => 'top-10-electric-vehicles-2025',
        'author' => 'Sarah Johnson',
        'date' => 'Jan 15, 2025',
        'comments' => 12,
        'image' => 'https://images.unsplash.com/photo-1560958089-b8a1929cea89?q=80&w=2071&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D',
        'excerpt' => 'Discover the most anticipated electric vehicles launching this year, from luxury sedans to affordable SUVs that will revolutionize the automotive industry.',
        'category' => 'Electric Vehicles'
    ],
    [
        'id' => 2,
        'title' => 'Essential Car Maintenance Tips for Winter',
        'slug' => 'car-maintenance-tips-winter',
        'author' => 'Mike Rodriguez',
        'date' => 'Jan 12, 2025',
        'comments' => 8,
        'image' => 'https://images.pexels.com/photos/14472323/pexels-photo-14472323.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2',
        'excerpt' => 'Keep your vehicle running smoothly during cold months with these expert maintenance tips and winter driving safety guidelines.',
        'category' => 'Maintenance'
    ],
    [
        'id' => 3,
        'title' => 'Hybrid vs Electric: Which is Right for You?',
        'slug' => 'hybrid-vs-electric-comparison',
        'author' => 'Emma Chen',
        'date' => 'Jan 10, 2025',
        'comments' => 15,
        'image' => 'https://images.pexels.com/photos/12353734/pexels-photo-12353734.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2',
        'excerpt' => 'Compare the benefits and drawbacks of hybrid and electric vehicles to make an informed decision for your next car purchase.',
        'category' => 'Buying Guide'
    ],
    [
        'id' => 4,
        'title' => 'The Future of Autonomous Driving Technology',
        'slug' => 'future-autonomous-driving',
        'author' => 'David Park',
        'date' => 'Jan 8, 2025',
        'comments' => 22,
        'image' => 'https://images.pexels.com/photos/17791131/pexels-photo-17791131/free-photo-of-steering-wheel-in-a-car.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2',
        'excerpt' => 'Explore the latest developments in self-driving technology and what it means for the future of transportation.',
        'category' => 'Technology'
    ],
    [
        'id' => 5,
        'title' => 'Best Family SUVs Under $50,000',
        'slug' => 'best-family-suvs-under-50k',
        'author' => 'Lisa Thompson',
        'date' => 'Jan 5, 2025',
        'comments' => 18,
        'image' => 'https://images.pexels.com/photos/9598104/pexels-photo-9598104.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2',
        'excerpt' => 'Find the perfect family SUV that combines safety, comfort, and value without breaking the bank.',
        'category' => 'Reviews'
    ],
    [
        'id' => 6,
        'title' => 'How to Increase Your Car\'s Resale Value',
        'slug' => 'increase-car-resale-value',
        'author' => 'Robert Wilson',
        'date' => 'Jan 3, 2025',
        'comments' => 9,
        'image' => 'https://images.pexels.com/photos/5800943/pexels-photo-5800943.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2',
        'excerpt' => 'Learn proven strategies to maintain and enhance your vehicle\'s value for when it\'s time to sell or trade in.',
        'category' => 'Tips'
    ]
];
?>

<!DOCTYPE html>
<html lang="zxx">

<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/3.5.0/css/flag-icon.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <title>QUOLYWHEELS - Blog</title>
    <style>
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

        /* Blog Item Enhancements */
        .blog__item {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            margin-bottom: 50px; /* Changed from 30px */
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .blog__item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .blog__item__pic {
            position: relative;
            height: 250px;
            overflow: hidden;
        }

        .blog__item__pic img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .blog__item:hover .blog__item__pic img {
            transform: scale(1.05);
        }

        .blog__item__pic ul {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0, 0, 0, 0.8));
            color: white;
            padding: 20px 15px 15px;
            margin: 0;
            list-style: none;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            font-size: 12px;
        }

        .blog__item__pic ul li {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .blog__item__pic ul li:first-child {
            font-weight: 600;
        }

        .blog__item__text {
            padding: 25px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .blog__item__text h5 {
            margin-bottom: 15px;
            line-height: 1.4;
        }

        .blog__item__text h5 a {
            color: var(--dark-color);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .blog__item__text h5 a:hover {
            color: var(--primary-color);
        }

        .blog__item__text p {
            color: var(--gray-color);
            line-height: 1.6;
            margin-bottom: 20px;
            flex: 1;
        }

        .blog__item__meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .blog__category {
            background: var(--primary-color);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .blog__read-more {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .blog__read-more:hover {
            color: var(--dark-color);
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

        /* Header Styles */
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

        .breadcrumb-option{
            background-image: url(img/blog/details/details-hero-bg.jpg);
        }

        /* Cart Badge Styles */
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

        /* Cart Sidebar Styles */
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
            transform: translateX(100%);
            transition: transform 0.3s ease;
        }

        .cart-sidebar.show {
            display: block;
            transform: translateX(0);
        }

        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e1e1e1;
        }

        .cart-header h3 {
            margin: 0;
            color: var(--dark-color);
            font-weight: 600;
        }

        .close-cart {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: var(--gray-color);
            transition: color 0.3s ease;
            padding: 5px;
            border-radius: 50%;
        }

        .close-cart:hover {
            color: var(--primary-color);
            background: #f8f9fa;
        }

        .cart-body {
            margin-top: 20px;
        }

        .selected-car-item {
            display: flex;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e1e1e1;
            align-items: center;
        }

        .selected-car-img {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            overflow: hidden;
            margin-right: 15px;
            flex-shrink: 0;
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
            color: var(--dark-color);
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
            padding: 5px;
            border-radius: 50%;
            margin-left: 10px;
        }

        .selected-car-remove:hover {
            color: var(--primary-color);
            background: #f8f9fa;
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
            margin: 0;
        }

        /* Toast Notification */
        .toast-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 9999;
            animation: slideInRight 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
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

        /* Responsive Styles */
        @media (max-width: 991px) {
            .navbar-nav {
                margin-top: 1rem;
                margin-bottom: 1rem;
            }

            .header .language-dropdown {
                margin: 0.5rem 0;
            }

            .header-buttons {
                flex-wrap: wrap;
                justify-content: center;
                margin-top: 1rem;
            }
        }

        @media (max-width: 575px) {
            .header-buttons {
                justify-content: space-around;
                width: 100%;
            }

            .btn-sm {
                font-size: 0.8rem;
                padding: 0.25rem 0.5rem;
            }

            .header .language-dropdown .btn-outline-danger {
                padding: 0.4rem 0.6rem;
            }
             .dropdown{
                margin-left: 10% !important;
            }
            
            .user-dropdown .dropdown-menu {
                min-width: 280px;
                margin-right: 1rem;
            }

            .cart-sidebar {
                width: 100%;
                max-width: 100%;
            }
        }

        /* Enhanced Sidebar Styles */
        .blog__sidebar {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid #e9ecef;
            position: sticky;
            top: 20px;
        }

        .blog__sidebar__search {
            margin-bottom: 40px;
            position: relative;
        }

        .blog__sidebar__search input {
            width: 100%;
            padding: 15px 50px 15px 20px;
            border: 2px solid #e9ecef;
            border-radius: 25px;
            background: white;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .blog__sidebar__search input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
            outline: none;
        }

        .blog__sidebar__search button {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .blog__sidebar__search button:hover {
            background: #c82333;
            transform: translateY(-50%) scale(1.05);
        }

        .blog__sidebar__feature,
        .blog__sidebar__categories,
        .blog__sidebar__tag,
        .blog__sidebar__newslatter {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid #f1f3f4;
        }

        .blog__sidebar h4 {
            color: var(--dark-color);
            font-weight: 700;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary-color);
            position: relative;
        }

        .blog__sidebar h4::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 30px;
            height: 2px;
            background: var(--primary-color);
        }

        .blog__sidebar__feature__item {
            padding: 15px 0;
            border-bottom: 1px solid #f1f3f4;
            transition: all 0.3s ease;
        }

        .blog__sidebar__feature__item:last-child {
            border-bottom: none;
        }

        .blog__sidebar__feature__item:hover {
            background: #f8f9fa;
            margin: 0 -15px;
            padding: 15px;
            border-radius: 8px;
        }

        .blog__sidebar__feature__item h6 a {
            color: var(--dark-color);
            text-decoration: none;
            font-weight: 600;
            line-height: 1.4;
            transition: color 0.3s ease;
        }

        .blog__sidebar__feature__item h6 a:hover {
            color: var(--primary-color);
        }

        .blog__sidebar__categories ul,
        .blog__sidebar__feature__item ul {
            list-style: none;
            padding: 0;
            margin: 10px 0 0 0;
        }

        .blog__sidebar__categories ul li {
            margin-bottom: 10px;
        }

        .blog__sidebar__categories ul li a {
            color: var(--gray-color);
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 20px;
            background: #f8f9fa;
            display: inline-block;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .blog__sidebar__categories ul li a:hover {
            background: var(--primary-color);
            color: white;
            transform: translateX(5px);
        }

        .blog__sidebar__feature__item ul li {
            display: inline-block;
            margin-right: 15px;
            font-size: 12px;
            color: var(--gray-color);
        }

        .blog__sidebar__tag a {
            display: inline-block;
            background: #f8f9fa;
            color: var(--gray-color);
            padding: 8px 15px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 12px;
            margin: 5px 5px 5px 0;
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
        }

        .blog__sidebar__tag a:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
            transform: translateY(-2px);
        }

        .blog__sidebar__newslatter form {
            margin-top: 15px;
        }

        .blog__sidebar__newslatter input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 25px;
            margin-bottom: 15px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .blog__sidebar__newslatter input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
            outline: none;
        }

        .blog__sidebar__newslatter button {
            width: 100%;
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .blog__sidebar__newslatter button:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
        }

        .blog__sidebar__newslatter p {
            color: var(--gray-color);
            font-size: 14px;
            line-height: 1.5;
        }

        /* Add spacing between blog grid rows */
        .blog .row > [class*="col-"] {
            margin-bottom: 30px;
        }

        /* Enhanced pagination */
        .pagination__option {
            margin-top: 50px;
            text-align: center;
        }

        .pagination__option a {
            display: inline-block;
            padding: 12px 18px;
            margin: 0 5px;
            background: white;
            color: var(--gray-color);
            text-decoration: none;
            border-radius: 8px;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .pagination__option a:hover,
        .pagination__option a.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
        }
    </style>
    <meta charset="UTF-8">
    <meta name="description" content="HVAC Template">
    <meta name="keywords" content="HVAC, unica, creative, html">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <script src="https://kit.fontawesome.com/dfba5e2447.js" crossorigin="anonymous"></script>
    <title data-translate="Blog - QUOLYWHEELS">QUOLYWHEELS - Blog</title>

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
</head>

<body>
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
                <span class="speed-unit" data-translate="km/h">km/h</span>
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
          
          <!-- QUOLYWHEELS Text -->
          <div class="quolywheels-text">QUOLYWHEELS</div>
        </div>
      </div>
        
    <!-- Header Section Begin -->
    <header class="header">
        <nav class="navbar navbar-expand-lg navbar-light bg-white">
            <div class="container">
                <a class="navbar-brand" href="./index.php">
                    <img src="img/logo zoom.png" alt="QUOLYWHEELS" class="img-fluid" style="max-height: 60px;">
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
                            <a class="nav-link text-black text-decoration-none mx-4 underline" href="blog.php"
                                data-translate="Blog">Blog</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-black text-decoration-none mx-4" href="about.php"
                                data-translate="About">About</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-black text-decoration-none mx-4" href="work.php"
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
                        <a href="book-test-drive.php" class="nav-link mr-2 d-flex align-items-center" data-bs-toggle="tooltip" title="Book Test Drive">
                            <i class="fa-solid fa-car-side"></i>
                        </a>
                        <a id="cart" href="#" class="nav-link mr-2 position-relative d-flex align-items-center" data-bs-toggle="tooltip" title="Shopping Cart">
                            <i class="fa-solid fa-cart-shopping"></i>
                            <span class="cart-badge" id="cartBadge">0</span>
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

    <!-- Breadcrumb Section -->
    <div class="breadcrumb-option" style="background-image: url('img/breadcrumb-about_us.jpg');">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 text-center">
                    <div class="breadcrumb__text">
                        <h2 data-translate="Blog">Blog</h2>
                        <div class="breadcrumb__links">
                            <a href="./index.php"><i class="fa fa-home"></i> <span
                                    data-translate="Home">Home</span></a>
                            <span data-translate="Automotive Blog">Automotive Blog</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Blog Section -->
    <section class="blog spad">
        <div class="container">
            <div class="row">
                <div class="col-lg-9">
                    <div class="row">
                        <?php foreach($blog_posts as $post): ?>
                        <div class="col-lg-6 col-md-6 col-sm-12">
                            <div class="blog__item">
                                <div class="blog__item__pic">
                                    <img src="<?= $post['image'] ?>" alt="<?= htmlspecialchars($post['title']) ?>">
                                    <ul>
                                        <li><i class="fa fa-calendar"></i> <?= $post['date'] ?></li>
                                    </ul>
                                </div>
                                <div class="blog__item__text">
                                    <h5><a href="blog-post.php?slug=<?= $post['slug'] ?>"><?= htmlspecialchars($post['title']) ?></a></h5>
                                    <p><?= htmlspecialchars($post['excerpt']) ?></p>
                                    <div class="blog__item__meta">
                                        <span class="blog__category"><?= $post['category'] ?></span>
                                        <a href="blog-post.php?slug=<?= $post['slug'] ?>" class="blog__read-more">
                                            Read More <i class="fa fa-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                </div>
                <div class="col-lg-3 col-md-6 col-sm-9">
                    <div class="blog__sidebar">
                        
                        <div class="blog__sidebar__feature">
                            <h4 data-translate="Featured Articles">Featured Articles</h4>
                            <div class="blog__sidebar__feature__item">
                                <h6><a href="blog-post.php?slug=top-10-electric-vehicles-2025">Top 10 Electric Vehicles to Watch in 2025</a></h6>
                                <ul>
                                    <li><i class="fa fa-calendar"></i> Jan 15, 2025</li>
                                </ul>
                            </div>
                            <div class="blog__sidebar__feature__item">
                                <h6><a href="blog-post.php?slug=car-maintenance-tips-winter">Essential Car Maintenance Tips for Winter</a></h6>
                                <ul>
                                    <li><i class="fa fa-calendar"></i> Jan 12, 2025</li>
                                </ul>
                            </div>
                            <div class="blog__sidebar__feature__item">
                                <h6><a href="blog-post.php?slug=hybrid-vs-electric-comparison">Hybrid vs Electric: Which is Right for You?</a></h6>
                                <ul>
                                    <li><i class="fa fa-calendar"></i> Jan 10, 2025</li>
                                </ul>
                            </div>
                        </div>
                        <div class="blog__sidebar__categories">
                            <h4 data-translate="Categories">Categories</h4>
                            <ul>
                                <li><a href="#" data-translate="Electric Vehicles">Electric Vehicles</a></li>
                                <li><a href="#" data-translate="Maintenance">Maintenance</a></li>
                                <li><a href="#" data-translate="Buying Guide">Buying Guide</a></li>
                                <li><a href="#" data-translate="Technology">Technology</a></li>
                                <li><a href="#" data-translate="Reviews">Reviews</a></li>
                                <li><a href="#" data-translate="Tips">Tips</a></li>
                            </ul>
                        </div>
                        <div class="blog__sidebar__tag">
                            <h4 data-translate="Popular Tags">Popular Tags</h4>
                            <a href="#" data-translate="Electric Cars">Electric Cars</a>
                            <a href="#" data-translate="BMW">BMW</a>
                            <a href="#" data-translate="Tesla">Tesla</a>
                            <a href="#" data-translate="Maintenance">Maintenance</a>
                            <a href="#" data-translate="SUV">SUV</a>
                            <a href="#" data-translate="Hybrid">Hybrid</a>
                            <a href="#" data-translate="Autonomous">Autonomous</a>
                            <a href="#" data-translate="Safety">Safety</a>
                        </div>
                        
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
                        <img src="img/logo zoom.png" alt="quolywheels Logo" class="img-fluid">
                    </div>
                    <div class="footer__contact-box">
                        <p><i class="fa fa-envelope"></i> quolywheels2025@gmail.com</p>
                        <p><i class="fa fa-phone"></i> +355 69 311 3209</p>
                    </div>
                    <div class="footer__social">
                        <a href="https://www.tiktok.com/@quolywheels1" class="social-btn tiktok" target="_blank">
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
    

    <!-- Cart JavaScript -->
    <script>
    let cart = JSON.parse(localStorage.getItem('quolywheels_cart')) || [];

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
    localStorage.setItem('quolywheels_cart', JSON.stringify(cart));

    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM loaded, initializing cart...');
        updateCartCounter();
        
        const cartButton = document.getElementById('cart');
        if (cartButton) {
            cartButton.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Cart button clicked');
                showCartSidebar();
            });
        } else {
            console.error('Cart button not found');
        }

        // Close cart button
        const closeCartButton = document.getElementById('closeCartdisplay');
        if (closeCartButton) {
            closeCartButton.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Close cart button clicked');
                closeCart();
            });
        }
    });

    function updateCartCounter() {
        const cartBadge = document.getElementById('cartBadge');
        const cartCount = cart ? cart.length : 0;
        
        console.log('Updating cart counter:', cartCount);
        
        if (cartBadge) {
            if (cartCount > 0) {
                cartBadge.textContent = cartCount;
                cartBadge.style.display = 'flex';
            } else {
                cartBadge.style.display = 'none';
            }
        } else {
            console.error('Cart badge element not found');
        }
    }

    function showCartSidebar() {
        const cartSidebar = document.getElementById('cartCars');
        const cartBody = document.getElementById('selectedCars');
        
        console.log('Showing cart sidebar');
        console.log('Cart sidebar element:', cartSidebar);
        console.log('Cart body element:', cartBody);
        
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
        
        cartSidebar.style.display = 'block';
        
        setTimeout(() => {
            cartSidebar.classList.add('show');
        }, 10);
    }

    function removeFromCart(vehicleId) {
        console.log('Removing item from cart:', vehicleId);
        
        if (!vehicleId) {
            showToast('error', 'Invalid item ID');
            return;
        }
        
        const originalLength = cart.length;
        cart = cart.filter(item => item && item.id !== vehicleId);
        
        if (cart.length < originalLength) {
            localStorage.setItem('quolywheels_cart', JSON.stringify(cart));
            updateCartCounter();
            showCartSidebar();
            
            showToast('success', 'Item removed from cart!');
        } else {
            showToast('error', 'Item not found in cart');
        }
    }

    function closeCart() {
        const cartSidebar = document.getElementById('cartCars');
        
        console.log('Closing cart');
        
        if (cartSidebar) {
            cartSidebar.classList.remove('show');
            
            setTimeout(() => {
                cartSidebar.style.display = 'none';
            }, 300);
        }
    }

    function showToast(type, message) {
        console.log('Showing toast:', type, message);
        
        const existingToasts = document.querySelectorAll('.toast-notification');
        existingToasts.forEach(toast => toast.remove());
        
        const toast = document.createElement('div');
        toast.className = `toast-notification ${type}`;
        toast.innerHTML = `
            <i class="fa fa-${type === 'success' ? 'check' : 'exclamation'}-circle"></i>
            ${message || 'Action completed'}
        `;
        
        // Add styles directly to ensure they work
        toast.style.position = 'fixed';
        toast.style.top = '20px';
        toast.style.right = '20px';
        toast.style.zIndex = '9999';
        toast.style.padding = '1rem 1.5rem';
        toast.style.borderRadius = '8px';
        toast.style.color = 'white';
        toast.style.display = 'flex';
        toast.style.alignItems = 'center';
        toast.style.gap = '10px';
        toast.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
        
        if (type === 'success') {
            toast.style.background = '#28a745';
        } else {
            toast.style.background = '#dc3545';
        }
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            if (toast && toast.parentNode) {
                toast.remove();
            }
        }, 3000);
    }

    // Test function to add items to cart (for testing purposes)
    function addTestItemToCart() {
        const testItem = {
            id: 'test-' + Date.now(),
            name: 'Test Vehicle',
            price: 25000,
            img: 'img/cars/car-1.jpg',
            type: 'sale'
        };
        
        cart.push(testItem);
        localStorage.setItem('quolywheels_cart', JSON.stringify(cart));
        updateCartCounter();
        showToast('success', 'Test item added to cart!');
    }

    // Add test button for debugging (remove in production)
    document.addEventListener('DOMContentLoaded', function() {
        // Uncomment the line below to add a test button for debugging
        // document.body.innerHTML += '<button onclick="addTestItemToCart()" style="position: fixed; bottom: 20px; left: 20px; z-index: 9999; background: red; color: white; padding: 10px;">Add Test Item</button>';
    });
    </script>

    <script>
        // Translations for the blog page
        const translations = {
            'en': {
                "Blog - QUOLYWHEELS": "Blog - QUOLYWHEELS",
                "Cars": "Cars",
                "Blog": "Blog",
                "Car Details": "Car Details",
                "About": "About",
                "Work": "Work",
                "Latest Blogs": "Latest Blogs",
                "Home": "Home",
                "Our Blogs": "Our Blogs",
                "By": "By",
                "Polly Williams": "Polly Williams",
                "8 Comment": "8 Comments",
                "Benjamin Franklin S Method Of": "Benjamin Franklin's Method Of",
                "Great business ideas when utilized effectively can save lots of money. This is not only easy for those who work full-time as an advertiser...": "Great business ideas when utilized effectively can save lots of money. This is not only easy for those who work full-time as an advertiser...",
                "Evelyn Lane": "Evelyn Lane",
                "Comment": "Comment",
                "Where To Look For Cheap Brochure": "Where To Look For Cheap Brochure",
                "Usually potential customers will visit home for business dealing. Print good amount of fliers and give it to anyone who is visiting home like family...": "Usually potential customers will visit home for business dealing. Print good amount of fliers and give it to anyone who is visiting home like family...",
                "Jessie Palmer": "Jessie Palmer",
                "Promotional Advertising Specialty": "Promotional Advertising Specialty",
                "For those who work outside home, employ college students to distribute fliers at supermarkets, community centers...": "For those who work outside home, employ college students to distribute fliers at supermarkets, community centers...",
                "Don Townsend": "Don Townsend",
                "Using Banner Stands To Increase": "Using Banner Stands To Increase",
                "Spread the word by the mouth. Talk to everyone about the product and ask them to talk about it to others...": "Spread the word by the mouth. Talk to everyone about the product and ask them to talk about it to others...",
                "Isabella Mann": "Isabella Mann",
                "2 Comment": "2 Comments",
                "3 Smart Reasons Why You Should": "3 Smart Reasons Why You Should",
                "It's a very powerful tool to increase the network and doesn't even cost anything. When receiving a casual call from family members and friends...": "It's a very powerful tool to increase the network and doesn't even cost anything. When receiving a casual call from family members and friends...",
                "6 Comment": "6 Comments",
                "Search Engine Optimization And": "Search Engine Optimization And",
                "Some businesses place a jar at the reception counter where the business cards can be dropped for future reference, while visiting them...": "Some businesses place a jar at the reception counter where the business cards can be dropped for future reference, while visiting them...",
                "Search...": "Search...",
                "Feature News": "Feature News",
                "Categories": "Categories",
                "Creativity": "Creativity",
                "Design": "Design",
                "Inspiration": "Inspiration",
                "News": "News",
                "Technology": "Technology",
                "Automotive": "Automotive",
                "Tag": "Tag",
                "Car Dealer": "Car Dealer",
                "bmw": "bmw",
                "Chevrolet": "Chevrolet",
                "ferrari": "ferrari",
                "Honda": "Honda",
                "Hatchback": "Hatchback",
                "Sedan": "Sedan",
                "SUV": "SUV",
                "Newsletter": "Newsletter",
                "Subscribe our newsletter to get": "Subscribe our newsletter to get",
                "Your email": "Your email",
                "Subscribe": "Subscribe",
                "Navigation": "Navigation",
                "Contact Us": "Contact Us",
                "Learn": "Learn",
                "About Us": "About Us",
                "Why Us?": "Why Us?",
                "Who are we?": "Who are we?",
                "Cart": "Cart",
                "Report": "Report",
                "Log In": "Log In",
                "English": "English",
                "Albanian": "Albanian",
                "Test Drive": "Test Drive",
                "km/h": "km/h",
                "Contact Information": "Contact Information",
                "Please call us at:": "Please call us at:",
                "Copy": "Copy",
                "Copied to clipboard!": "Copied to clipboard!",
                "Close": "Close",
                "© Copyright 2025 QUOLYWHEELS. All Rights Reserved.": "© Copyright 2025 QUOLYWHEELS. All Rights Reserved.",
                "Facebook": "Facebook",
                "Twitter": "Twitter",
                "Instagram": "Instagram",
                "Your Cart": "Your Cart",
                "Your cart is empty": "Your cart is empty",
                "Featured Articles": "Featured Articles",
                "Popular Tags": "Popular Tags",
                "Electric Cars": "Electric Cars",
                "BMW": "BMW",
                "Tesla": "Tesla",
                "Maintenance": "Maintenance",
                "Hybrid": "Hybrid",
                "Autonomous": "Autonomous",
                "Safety": "Safety",
                "Electric Vehicles": "Electric Vehicles",
                "Buying Guide": "Buying Guide",
                "Reviews": "Reviews",
                "Tips": "Tips",
                "Automotive Blog": "Automotive Blog"
            },
            'sq': {
                "Blog - QUOLYWHEELS": "Blog - QUOLYWHEELS",
                "Cars": "Makinat",
                "Blog": "Blog",
                "Car Details": "Detajet e Makinës",
                "About": "Rreth Nesh",
                "Work": "Puna",
                "Latest Blogs": "Blogjet e Fundit",
                "Home": "Kryefaqja",
                "Our Blogs": "Blogjet Tona",
                "By": "Nga",
                "Polly Williams": "Polly Williams",
                "8 Comment": "8 Komente",
                "Benjamin Franklin S Method Of": "Metoda e Benjamin Franklin",
                "Great business ideas when utilized effectively can save lots of money. This is not only easy for those who work full-time as an advertiser...": "Idetë e mira të biznesit kur përdoren në mënyrë efektive mund të kursejnë shumë para. Kjo nuk është e lehtë vetëm për ata që punojnë me kohë të plotë si reklamues...",
                "Evelyn Lane": "Evelyn Lane",
                "Comment": "Koment",
                "Where To Look For Cheap Brochure": "Ku të Kërkoni Broshura të Lira",
                "Usually potential customers will visit home for business dealing. Print good amount of fliers and give it to anyone who is visiting home like family...": "Zakonisht klientët potencial do të vizitojnë shtëpinë për marrëdhënie biznesi. Printoni një sasi të mirë të flerëve dhe jepini ato kujdo që viziton shtëpinë si familja...",
                "Jessie Palmer": "Jessie Palmer",
                "Promotional Advertising Specialty": "Specialiteti i Reklamimit Promocional",
                "For those who work outside home, employ college students to distribute fliers at supermarkets, community centers...": "Për ata që punojnë jashtë shtëpisë, punësoni studentë të kolegjit për të shpërndarë flerë në supermarketet, qendrat komunitare...",
                "Don Townsend": "Don Townsend",
                "Using Banner Stands To Increase": "Përdorimi i Standeve të Bannerëve për të Rritur",
                "Spread the word by the mouth. Talk to everyone about the product and ask them to talk about it to others...": "Shpërndajeni fjalën me gojë. Flisni me të gjithë për produktin dhe kërkoni që ata të flasin për të me të tjerët...",
                "Isabella Mann": "Isabella Mann",
                "2 Comment": "2 Komente",
                "3 Smart Reasons Why You Should": "3 Arsyet e Mençura Pse Duhet",
                "It's a very powerful tool to increase the network and doesn't even cost anything. When receiving a casual call from family members and friends...": "Është një mjet shumë i fuqishëm për të rritur rrjetin dhe nuk kushton asgjë. Kur merrni një telefonatë të rastësishme nga anëtarët e familjes dhe miqtë...",
                "6 Comment": "6 Komente",
                "Search Engine Optimization And": "Optimizimi i Motorëve të Kërkimit Dhe",
                "Some businesses place a jar at the reception counter where the business cards can be dropped for future reference, while visiting them...": "Disa biznese vendosin një kavanoz në banakun e pritjes ku kartvizitat mund të hidhen për referencë të ardhshme, ndërsa i vizitojnë ato...",
                "Search...": "Kërko...",
                "Feature News": "Lajme Kryesore",
                "Categories": "Kategoritë",
                "Creativity": "Kreativitet",
                "Design": "Dizajn",
                "Inspiration": "Frymëzim",
                "News": "Lajme",
                "Technology": "Teknologji",
                "Automotive": "Automobilizëm",
                "Tag": "Etiketë",
                "Car Dealer": "Shitës Makinash",
                "bmw": "bmw",
                "Chevrolet": "Chevrolet",
                "ferrari": "ferrari",
                "Honda": "Honda",
                "Hatchback": "Hatchback",
                "Sedan": "Sedan",
                "SUV": "SUV",
                "Newsletter": "Buletini",
                "Subscribe our newsletter to get": "Regjistrohu për buletinin tonë për të marrë",
                "Your email": "Email-i juaj",
                "Subscribe": "Regjistrohu",
                "Navigation": "Navigimi",
                "Contact Us": "Na Kontaktoni",
                "Learn": "Mësoni",
                "About Us": "Rreth Nesh",
                "Why Us?": "Pse Ne?",
                "Who are we?": "Kush jemi ne?",
                "Cart": "Shporta",
                "Report": "Raporto",
                "Log In": "Hyr",
                "English": "Anglisht",
                "Albanian": "Shqip",
                "Test Drive": "Test Drive",
                "km/h": "km/h",
                "Contact Information": "Informacioni i Kontaktit",
                "Please call us at:": "Ju lutemi na telefononi në:",
                "Copy": "Kopjo",
                "Copied to clipboard!": "U kopjua në clipboard!",
                "Close": "Mbyll",
                "© Copyright 2025 QUOLYWHEELS. All Rights Reserved.": "© E drejta e autorit 2025 QUOLYWHEELS. Të gjitha të drejtat e rezervuara.",
                "Facebook": "Facebook",
                "Twitter": "Twitter",
                "Instagram": "Instagram",
                "Your Cart": "Shporta Juaj",
                "Your cart is empty": "Shporta juaj është bosh",
                "Featured Articles": "Artikuj të Zgjedhur",
                "Popular Tags": "Etiketat Popullore",
                "Electric Cars": "Makinat Elektrike",
                "BMW": "BMW",
                "Tesla": "Tesla",
                "Maintenance": "Mirëmbajtja",
                "Hybrid": "Hibrid",
                "Autonomous": "Autonom",
                "Safety": "Siguria",
                "Electric Vehicles": "Automjetet Elektrike",
                "Buying Guide": "Udhëzues Blerje",
                "Reviews": "Recensione",
                "Tips": "Këshilla",
                "Automotive Blog": "Blog Automobilistik"
            }
        };

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

            

            // Phone modal functionality
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
        });
        
        // Loading screen functionality
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
</body>
</html>
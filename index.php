<?php
include_once 'config.php';

// Get user data if logged in
$user_data = null;
if (isset($_SESSION['user_id'])) {
    $user_stmt = $conn->prepare("SELECT full_name, email, profile_image, role FROM users WHERE id = ?");
    $user_stmt->bind_param("i", $_SESSION['user_id']);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    if ($user_result) {
        $user_data = $user_result->fetch_assoc();
    }
    $user_stmt->close();
}

// UPDATED: Get featured vehicles for homepage - EXCLUDE vehicles in active rentals/sales
$featured_query = "
    SELECT v.* FROM vehicles v 
    WHERE v.status IN ('In Stock', 'Reserved') 
    AND v.id NOT IN (
        SELECT DISTINCT vehicle_id FROM rentals 
        WHERE status IN ('active', 'pending', 'confirmed') 
        AND vehicle_id IS NOT NULL
    )
    AND v.id NOT IN (
        SELECT DISTINCT vehicle_id FROM sales 
        WHERE status IN ('active', 'pending', 'confirmed', 'processing') 
        AND vehicle_id IS NOT NULL
    )
    ORDER BY v.added_at DESC 
    LIMIT 9
";
$featured_vehicles = $conn->query($featured_query);

?>
<!DOCTYPE html>
<html lang="zxx">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="icon" href="img/autobuba logo.png" type="image/png">
    <title>AUTOBUBA - Home</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Owl Carousel -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css">
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="loading.css">
    <link rel="stylesheet" href="css/cart.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.7.0/nouislider.min.css">

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
    <link rel="stylesheet" href="css/cart.css">

    <!-- Flag Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/3.5.0/css/flag-icon.min.css">

    <!-- Font Awesome -->
    <script src="https://kit.fontawesome.com/dfba5e2447.js" crossorigin="anonymous"></script>
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

        /* Cart Styles from car.html */
        .cart-cars {
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

        .close-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: var(--gray-color);
            transition: color 0.3s ease;
        }

        .close-btn:hover {
            color: var(--primary-color);
        }

        .selected-cars-container {
            margin-top: 40px;
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

        .cart-cars.show {
            display: block;
        }

        .header__nav__widget a,
        .header__nav__widget .btn {
            color: #000;
            margin-left: 15px;
        }

        .header__nav__widget .btn {
            background-color: #d90429;
            border: none;
            color: #fff;
        }

        /* Fix modal z-index */
        .modal {
            z-index: 1060 !important;
        }

        .modal-backdrop {
            z-index: 1040 !important;
        }

        /* Fix car card dimensions */
        #searchModal .car__item {
            width: 100%;
            min-width: 300px;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        #searchModal .car__item__pic {
            flex: 1;
            min-height: 200px;
        }

        #searchModal .car__item__pic img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        #searchModal .car__item__text {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        #searchModal .car-specs {
            display: flex;
            gap: 15px;
            margin: 15px 0;
        }

        #searchModal .car-spec {
            flex: 1;
            text-align: center;
            background: #f8f9fa;
            padding: 8px;
            border-radius: 5px;
        }

        #searchModal .car-actions {
            display: flex;
            gap: 10px;
            margin: 15px 0;
        }

        #searchModal .view-more {
            white-space: nowrap;
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

        @media (max-width: 768px) {
            .user-dropdown .dropdown-menu {
                min-width: 280px;
                margin-right: 1rem;
            }
            
            .header-buttons {
                gap: 0.5rem;
            }
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

.car-actions {
    display: flex;
    gap: 8px;
}

.test-drive-btn {
    background: linear-gradient(135deg, #28a745, #20c997);
    border: none;
    color: white;
    transition: all 0.3s ease;
}

.test-drive-btn:hover {
    background: linear-gradient(135deg, #218838, #1ea085);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
    color: white;
}

.add-to-cart-btn {
    transition: all 0.3s ease;
}

.add-to-cart-btn:hover {
    background: #dc3545;
    border-color: #dc3545;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
}

.car__item {
    transition: all 0.3s ease;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.car__item:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.car-option-badge {
    position: absolute;
    top: 10px;
    left: 10px;
    background: rgba(217, 4, 41, 0.9);
    color: white;
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 12px;
    font-weight: 600;
}

.car-specs {
    display: flex;
    gap: 15px;
    margin: 10px 0;
    font-size: 14px;
    color: #666;
}

.car-spec {
    display: flex;
    align-items: center;
    gap: 3px;
}

.view-more {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    color: #495057;
    transition: all 0.3s ease;
}

.view-more:hover {
    background: #e9ecef;
    border-color: #adb5bd;
    color: #495057;
}

.alert-warning {
    background: linear-gradient(135deg, #fff3cd, #ffeaa7);
    border: 1px solid #ffeaa7;
    border-radius: 10px;
}

.alert-warning .alert-link {
    color: #d90429;
    font-weight: 600;
    text-decoration: none;
}

.alert-warning .alert-link:hover {
    text-decoration: underline;
}

/* Make the search modal much wider and taller */
#searchModal .modal-dialog {
    max-width: 90vw !important;
    width: 90vw !important;
    height: 85vh !important;
    margin: 2.5vh auto !important;
}

#searchModal .modal-content {
    height: 100% !important;
    border-radius: 15px !important;
}

#searchModal .modal-body {
    height: calc(100% - 130px) !important;
    overflow-y: auto !important;
    padding: 20px !important;
}

#searchModal .modal-header {
    flex-shrink: 0 !important;
}

#searchModal .modal-footer {
    flex-shrink: 0 !important;
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
                            <a class="nav-link text-black text-decoration-none mx-4" href="about.php"
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
                        
                        <!-- Replace the existing cart link with this -->
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
                            // Get user data
                            $user_stmt = $conn->prepare("SELECT full_name, email, profile_image, role FROM users WHERE id = ?");
                            $user_stmt->bind_param("i", $_SESSION['user_id']);
                            $user_stmt->execute();
                            $user_data = $user_stmt->get_result()->fetch_assoc();
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
                                                <?php if ($bookings_count > 0): ?>
                                                    <span class="notification-badge"><?= $bookings_count ?></span>
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

<!-- Hero Section Begin -->
<section class="hero spad set-bg" style="background-image: url('img/Breadcrumb_Img.jpg');">
    <div class="overlay"></div>
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-7 col-md-12 mb-5 mb-lg-0">
                <div class="hero__text">
                    <div class="hero__text__title">
                        <span data-translate="IT ALL STARTS WITH A DREAM" class="text-white">IT ALL STARTS WITH A DREAM</span>
                        <h2 class="mb-2 text-white"
                            data-translate="All of those cars were once just a dream in somebody's head. - Peter Gabriel">
                            All of those cars were once just a dream in somebody's head. - Peter Gabriel</h2>
                    </div>
                    <div class="d-flex flex-wrap">
                        <a href="Book a Test Drive.html" class="primary-btn mr-3 mb-3"><img src="img/wheel.png"
                                alt=""> <span data-translate="Test Drive">Test Drive</span></a>
                        <a href="#trustedName" class="primary-btn more-btn mb-3" data-translate="Learn More">Learn
                            More</a>
                    </div>
                </div>
            </div>

            <div class="col-lg-5 col-md-12">
                <div class="hero__tab shadow-lg rounded">
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-toggle="tab" href="#tabs-1" role="tab"
                                data-translate="Car Rental">Car Rental</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#tabs-2" role="tab"
                                data-translate="Buy Car">Buy Car</a>
                        </li>
                    </ul>

                    <div class="tab-content bg-white p-4">
                        <!-- Car Rental Form -->
                        <div class="tab-pane active" id="tabs-1" role="tabpanel">
                            <div class="hero__tab__form">
                                <h2 class="mb-4" data-translate="Rent Your Dream Car">Rent Your Dream Car</h2>
                                <form id="rentalForm" action="search-results.php" method="GET">
                                    <input type="hidden" name="type" value="rent">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <select class="form-control" name="year" id="rentYear">
                                                <option value="" data-translate="Select Year">Select Year</option>
                                                <?php for($y = date('Y'); $y >= 2015; $y--): ?>
                                                    <option value="<?= $y ?>"><?= $y ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <select class="form-control" name="brand" id="rentBrand">
                                                <option value="" data-translate="Select Brand">Select Brand</option>
                                                <?php 
                                                // Get ALL brands (no status filter)
                                                $brands = $conn->query("SELECT DISTINCT make FROM vehicles");
                                                while($brand = $brands->fetch_assoc()):
                                                ?>
                                                    <option value="<?= $brand['make'] ?>">
                                                        <?= $brand['make'] ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <select class="form-control" name="model" id="rentModel">
                                                <option value="" data-translate="Select Model">Select Model</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <select class="form-control" name="mileage" id="rentMileage">
                                                <option value="" data-translate="Select Mileage">Select Mileage</option>
                                                <option value="10000">Under 10,000</option>
                                                <option value="30000">Under 30,000</option>
                                                <option value="50000">Under 50,000</option>
                                                <option value="100000">Under 100,000</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="car-price">
                                        <p data-translate="Price Range:">Price Range:</p>
                                        <div class="price-range-wrap">
                                            <div class="price-range" id="rentPriceRange"></div>
                                            <div class="range-slider">
                                                <div class="price-input">
                                                    <input type="text" id="amount" readonly>
                                                    <input type="hidden" id="rentMinPrice" name="min_price" value="0">
                                                    <input type="hidden" id="rentMaxPrice" name="max_price" value="100000">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit" class="site-btn w-100 mt-3"
                                        data-translate="Search">Search</button>
                                </form>
                            </div>
                        </div>

                        <!-- Buy Car Form -->
                        <div class="tab-pane" id="tabs-2" role="tabpanel">
                            <div class="hero__tab__form">
                                <h2 class="mb-4" data-translate="Buy Your Dream Car">Buy Your Dream Car</h2>
                                <form id="buyForm" action="search-results.php" method="GET">
                                    <input type="hidden" name="type" value="buy">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <select class="form-control" name="year" id="buyYear">
                                                <option value="" data-translate="Select Year">Select Year</option>
                                                <?php for($y = date('Y'); $y >= 2015; $y--): ?>
                                                    <option value="<?= $y ?>"><?= $y ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <select class="form-control" name="brand" id="buyBrand">
                                                <option value="" data-translate="Select Brand">Select Brand</option>
                                                <?php 
                                                // Get ALL brands (no status filter)
                                                $brands = $conn->query("SELECT DISTINCT make FROM vehicles");
                                                while($brand = $brands->fetch_assoc()):
                                                ?>
                                                    <option value="<?= $brand['make'] ?>">
                                                        <?= $brand['make'] ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <select class="form-control" name="model" id="buyModel">
                                                <option value="" data-translate="Select Model">Select Model</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <select class="form-control" name="mileage" id="buyMileage">
                                                <option value="" data-translate="Select Mileage">Select Mileage</option>
                                                <option value="10000">Under 10,000</option>
                                                <option value="30000">Under 30,000</option>
                                                <option value="50000">Under 50,000</option>
                                                <option value="100000">Under 100,000</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="car-price">
                                        <p data-translate="Price Range:">Price Range:</p>
                                        <div class="price-range-wrap">
                                            <div class="price-range" id="buyPriceRange"></div>
                                            <div class="range-slider">
                                                <div class="price-input">
                                                    <input type="text" id="buyAmount" readonly>
                                                    <input type="hidden" id="buyMinPrice" name="min_price" value="0">
                                                    <input type="hidden" id="buyMaxPrice" name="max_price" value="100000">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit" class="site-btn w-100 mt-3"
                                        data-translate="Search">Search</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Search Results Modal -->
<div id="searchModal" class="modal fade" tabindex="-1" aria-labelledby="searchModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="searchModalLabel" data-translate="Search Results">Search Results</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4" id="searchResults">
                    <!-- Search results will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-translate="Close">Close</button>
                <a href="car.php" class="btn btn-danger" data-translate="View All Cars">View All Cars</a>
            </div>
        </div>
    </div>
</div>

<!-- Hero Section End -->

    <!-- Services Section Begin -->
    <section class="services spad">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <div class="section-title">
                        <span data-translate="Our Services">Our Services</span>
                        <h2 data-translate="What We Offer">What We Offer</h2>
                        <p
                            data-translate="Well-maintained, clean, and sanitized cars, excellent customer service and fast & hassle-free rental process">
                            Well-maintained, clean, and sanitized cars, excellent customer service and fast &
                            hassle-free rental process</p>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="services__item h-100 text-center p-4">
                        <img src="img/services/services-1.png" alt="" class="img-fluid mb-4">
                        <h5 data-translate="Renting Cars">Renting Cars</h5>
                        <p
                            data-translate="Need a reliable ride? We offer a seamless car rental experience with a wide range of vehicles to fit your needs.">
                            Need a reliable ride? We offer a seamless car rental experience with a wide range of
                            vehicles to fit your needs.</p>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="services__item h-100 text-center p-4">
                        <img src="img/services/services-2.png" alt="" class="img-fluid mb-4">
                        <h5 data-translate="Buying Cars">Buying Cars</h5>
                        <p
                            data-translate="Looking for your next car? We offer a hassle-free buying experience with a selection of high-quality vehicles.">
                            Looking for your next car? We offer a hassle-free buying experience with a selection of
                            vehicles to fit your needs.</p>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="services__item h-100 text-center p-4">
                        <img src="img/services/services-3.png" alt="" class="img-fluid mb-4">
                        <h5 data-translate="Quality Assurance">Quality Assurance</h5>
                        <p
                            data-translate="Well-maintained, clean, and reliable vehicles, ensuring a safe and comfortable drive every time.">
                            Well-maintained, clean, and reliable vehicles, ensuring a safe and comfortable drive every
                            time.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Services Section End -->

    <!-- Feature Section -->
    <section id="trustedName" class="feature spad">
        <div class="container">
            <div class="row align-items-center">
                <!-- Text Content Column -->
                <div class="col-lg-4 col-md-12 mb-4 "> <!-- Changed mb-5 to mb-4 -->
                    <div class="feature__text">
                        <div class="section-title text-left">
                            <span data-translate="Our Feature">Our Feature</span>
                            <h2 data-translate="We Are a Trusted Name In Auto">We Are a Trusted Name In Auto</h2>
                        </div>
                        <div class="feature__text__desc">
                            <p class="mb-3"
                                data-translate="As a trusted name in the automotive industry, we don't just meet expectations—we aim to exceed them. Our commitment to innovation, professionalism, and building lasting relationships has solidified our place as a leader in the field.">
                                As a trusted name in the automotive industry, we don't just meet expectations—we aim to
                                exceed them. Our commitment to innovation, professionalism, and building lasting
                                relationships has solidified our place as a leader in the field.
                            </p>
                        </div>
                        <div class="feature__text__btn mt-3"> <!-- Added mt-3 -->
                            <a href="about.php#testimonies" class="primary-btn partner-btn"
                                data-translate="Our Reviews">Our Reviews</a>
                        </div>
                    </div>
                </div>

                <!-- Feature Icons Column -->
                <div class="col-lg-8 col-md-12 pl-lg-4"> <!-- Added left padding on lg -->
                    <div class="row g-2"> <!-- Changed gutter to g-2 (smallest spacing) -->
                        <!-- Feature Items -->
                        <div class="col-4 col-sm-4 col-md-4 col-lg-4 px-1"> <!-- Added px-1 -->
                            <div class="feature__item text-center p-2"> <!-- Added p-2 -->
                                <div class="feature__item__icon mb-2"> <!-- Reduced mb-3 to mb-2 -->
                                    <img src="img/feature/feature-1.png" alt="Engine" class="img-fluid"
                                        style="max-height: 50px;">
                                </div>
                                <h6 class="mb-0" data-translate="Engine">Engine</h6> <!-- Removed bottom margin -->
                            </div>
                        </div>

                        <!-- Repeat for other 5 feature items with same structure -->
                        <div class="col-4 col-sm-4 col-md-4 col-lg-4 px-1">
                            <div class="feature__item text-center p-2">
                                <div class="feature__item__icon mb-2">
                                    <img src="img/feature/feature-2.png" alt="Turbo" class="img-fluid"
                                        style="max-height: 50px;">
                                </div>
                                <h6 class="mb-0" data-translate="Turbo">Turbo</h6>
                            </div>
                        </div>

                        <div class="col-4 col-sm-4 col-md-4 col-lg-4 px-1">
                            <div class="feature__item text-center p-2">
                                <div class="feature__item__icon mb-2">
                                    <img src="img/feature/feature-3.png" alt="Cooling" class="img-fluid"
                                        style="max-height: 50px;">
                                </div>
                                <h6 class="mb-0" data-translate="Cooling">Cooling</h6>
                            </div>
                        </div>

                        <div class="col-4 col-sm-4 col-md-4 col-lg-4 px-1">
                            <div class="feature__item text-center p-2">
                                <div class="feature__item__icon mb-2">
                                    <img src="img/feature/feature-4.png" alt="Suspension" class="img-fluid"
                                        style="max-height: 50px;">
                                </div>
                                <h6 class="mb-0" data-translate="Suspension">Suspension</h6>
                            </div>
                        </div>

                        <div class="col-4 col-sm-4 col-md-4 col-lg-4 px-1">
                            <div class="feature__item text-center p-2">
                                <div class="feature__item__icon mb-2">
                                    <img src="img/feature/feature-5.png" alt="Electrical" class="img-fluid"
                                        style="max-height: 50px;">
                                </div>
                                <h6 class="mb-0" data-translate="Electrical">Electrical</h6>
                            </div>
                        </div>

                        <div class="col-4 col-sm-4 col-md-4 col-lg-4 px-1">
                            <div class="feature__item text-center p-2">
                                <div class="feature__item__icon mb-2">
                                    <img src="img/feature/feature-6.png" alt="Brakes" class="img-fluid"
                                        style="max-height: 50px;">
                                </div>
                                <h6 class="mb-0" data-translate="Brakes">Brakes</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

<!-- CAR LISTINGS -->
<section class="car spad">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center">
                <div class="section-title">
                    <span data-translate="Our Car">Our Car</span>
                    <h2 data-translate="Best Vehicle Offers">Best Vehicle Offers</h2>
                </div>
                <ul class="filter__controls">
                    <li data-filter="all" data-translate="Most Researched">Our Most Researched Vehicles</li>
                </ul>
            </div>
        </div>

        <!-- Login Prompt for Non-logged Users -->
        <?php if (!isset($_SESSION['user_id'])): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <div class="alert alert-warning text-center">
                        <strong>Want to book a test drive?</strong> 
                        <a href="login.php" class="alert-link">Login</a> or 
                        <a href="login.php" class="alert-link">Register</a> to schedule your test drive appointment.
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="row car-filter">
            <?php 
                // UPDATED: Get vehicles excluding those in active rentals/sales
                $sql = "
                    SELECT v.* FROM vehicles v 
                    WHERE v.status IN ('In Stock', 'Reserved') 
                    AND v.id NOT IN (
                        SELECT DISTINCT vehicle_id FROM rentals 
                        WHERE status IN ('active', 'pending', 'confirmed') 
                        AND vehicle_id IS NOT NULL
                    )
                    AND v.id NOT IN (
                        SELECT DISTINCT vehicle_id FROM sales 
                        WHERE status IN ('active', 'pending', 'confirmed', 'processing') 
                        AND vehicle_id IS NOT NULL
                    )
                    ORDER BY v.added_at DESC
                ";
                $result = $conn->query($sql);
                
                if ($result && $result->num_rows > 0):
                    while($row = $result->fetch_assoc()): 
                        // Show "Available" for all vehicles regardless of status
                        $badge_type = 'Available';
                        $price_display = '$'.number_format($row['price'], 2);
                        
                        // Determine filter classes
                        $filter_classes = ['all'];
                        
                        // Add "sale" class to recently added cars (within the last 30 days)
                        if (isset($row['added_at']) && $row['added_at']) {
                            try {
                                $added_date = new DateTime($row['added_at']);
                                $now = new DateTime();
                                $days_since_added = $now->diff($added_date)->days;
                                if ($days_since_added <= 30) {
                                    $filter_classes[] = 'sale';
                                }
                            } catch (Exception $e) {
                                // Handle date parsing error silently
                            }
                        }
                        
                        // Add "discount" class to cars with price less than average
                        if ($row['price'] < 30000) {
                            $filter_classes[] = 'discount';
                        }
                        
                        $filter_class = implode(' ', $filter_classes);
                        
                        // Handle image path
                        $image_path = $row['image_path'] ?? 'img/cars/car-1.jpg';
                        if (!$image_path || !file_exists($image_path)) {
                            $default_images = [
                                'img/cars/car-1.jpg',
                                'img/cars/car-2.jpg', 
                                'img/cars/car-3.jpg',
                                'img/cars/car-4.jpg'
                            ];
                            $image_path = $default_images[array_rand($default_images)];
                        }
                ?>
                    <div class="col-lg-3 col-md-4 col-sm-6 mix <?= $filter_class ?>">
                        <div class="car__item">
                            <div class="car__item__pic position-relative">
                                <img src="<?= htmlspecialchars($image_path) ?>" 
                                    alt="<?= htmlspecialchars($row['make'].' '.$row['model']) ?>" 
                                    class="img-fluid w-100"
                                    style="height: 180px; object-fit: cover;">
                                <span class="car-option-badge"><?= htmlspecialchars($badge_type) ?></span>
                            </div>
                            <div class="car__item__text">
                                <div class="car__item__text__inner">
                                    <span class="badge bg-light text-dark px-2 py-1 rounded-2 mb-2">
                                        <?= htmlspecialchars($row['year'] ?? 'N/A') ?>
                                    </span>
                                    <h5>
                                        <a href="car-details.php?id=<?= $row['id'] ?>">
                                            <?= htmlspecialchars($row['make'].' '.$row['model']) ?>
                                        </a>
                                    </h5>
                                    <div class="car-specs">
                                        <div class="car-spec">
                                            <span><?= number_format($row['mileage'] ?? 0) ?></span> mi
                                        </div>
                                        <div class="car-spec">Auto</div>
                                        <div class="car-spec">
                                            <span><?= htmlspecialchars($row['horsepower'] ?? 'N/A') ?></span> hp
                                        </div>
                                    </div>
                                </div>
                                <div class="car__item__price">
                                    <h6><?= $price_display ?></h6>
                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                        <div class="car-actions d-flex gap-2">
                                            <!-- Add To Cart -->
                                            <button class="btn btn-sm btn-outline-danger add-to-cart-btn" 
                                                    title="Add to Cart"
                                                    data-id="<?= $row['id'] ?>"
                                                    data-name="<?= htmlspecialchars($row['make'].' '.$row['model']) ?>"
                                                    data-price="<?= $row['price'] ?>"
                                                    data-img="<?= htmlspecialchars($image_path) ?>"
                                                    data-type="purchase">
                                                <i class="fa fa-cart-plus"></i>
                                            </button>
                                            
                                            <!-- Test Drive Button -->
                                            <?php if (isset($_SESSION['user_id'])): ?>
                                                <a href="book-test-drive.php?car_id=<?= $row['id'] ?>" 
                                                   class="btn btn-sm btn-success test-drive-btn"
                                                   title="Book Test Drive">
                                                    <i class="fa fa-car"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="login.php?redirect=<?= urlencode('book-test-drive.php?car_id=' . $row['id']) ?>" 
                                                   class="btn btn-sm btn-success test-drive-btn"
                                                   title="Login to Book Test Drive">
                                                    <i class="fa fa-car"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- View More Button -->
                                        <a href="car-details.php?id=<?= $row['id'] ?>" 
                                           class="btn btn-sm btn-light view-more">
                                            View More
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; else: ?>
                    <div class="col-12 text-center py-5">
                        <div class="alert alert-info">
                            <h4 data-translate="No vehicles available">No vehicles available</h4>
                            <p>Please check back later for new arrivals.</p>
                            <?php if (isset($_SESSION['user_id']) && in_array($_SESSION['role'] ?? '', ['admin', 'manager'])): ?>
                                <a href="car-dashboard/vehicles.php" class="btn btn-primary mt-2">Add Vehicles</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>


   <!-- Chooseus Section Begin -->
<section id="chooseUs" class="py-5 mt-2 mb-6 position-relative">
    <div class="container">
        <div class="row">
            <div class="col-lg-6 col-md-12 mb-4 mb-lg-0">
                <div class="pe-lg-4">
                    <div class="mb-4">
                        <h2 class="display-5 fw-bold mb-3" data-translate="Why People Choose Us">Why People Choose Us</h2>
                        <p class="lead text-muted mb-4" data-translate="People choose us because we deliver unmatched quality, personalized service, and reliability that you can always count on.">
                            "People choose us because we deliver unmatched quality, personalized service, and
                            reliability that you can always count on."
                        </p>
                    </div>
                    <ul class="list-unstyled mb-4">
                        <li class="d-flex align-items-center mb-3">
                            <i class="fa fa-check-circle text-success me-3 fs-5"></i> 
                            <span class="text-dark" data-translate="Unmatched quality and attention to detail.">
                                Unmatched quality and attention to detail.
                            </span>
                        </li>
                        <li class="d-flex align-items-center mb-3">
                            <i class="fa fa-check-circle text-success me-3 fs-5"></i> 
                            <span class="text-dark" data-translate="Exceptional customer service tailored to your needs.">
                                Exceptional customer service tailored to your needs.
                            </span>
                        </li>
                        <li class="d-flex align-items-center mb-3">
                            <i class="fa fa-check-circle text-success me-3 fs-5"></i> 
                            <span class="text-dark" data-translate="Affordable solutions without compromising reliability.">
                                Affordable solutions without compromising reliability.
                            </span>
                        </li>
                        <li class="d-flex align-items-center mb-3">
                            <i class="fa fa-check-circle text-success me-3 fs-5"></i> 
                            <span class="text-dark" data-translate="A proven track record of trust and excellence.">
                                A proven track record of trust and excellence.
                            </span>
                        </li>
                    </ul>
                    <a href="about.html" class="btn btn-danger btn-lg px-4 py-2" data-translate="About Us">About Us</a>
                </div>
            </div>
            <div class="col-lg-6 col-md-12 d-none d-lg-block">
                <div class="position-relative h-100">
                    <img src="img/image.jpg" alt="Why Choose Us" class="img-fluid rounded shadow-lg w-100 h-100 object-fit-cover">
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Chooseus Section End -->

<!-- Add extra bottom margin with a spacer div -->
<div class="mb-5 pb-5"></div>


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

<!-- Simple Cart Sidebar - Replace your existing cart sidebar with this -->
<div class="cart-sidebar" id="cartCars" style="display: none;">
    <div class="cart-header">
        <h3 data-translate="Your Cart">Your Cart</h3>
        <button id="closeCartdisplay" class="close-cart">
            <i class="fa fa-times"></i>
        </button>
    </div>
    <div class="cart-body" id="selectedCars">
        <!-- Cart items will be dynamically inserted here -->
        <div class="empty-cart">
            <i class="fa fa-shopping-cart"></i>
            <p data-translate="Your cart is empty">Your cart is empty</p>
        </div>
    </div>
    
    <!-- Additional contact message with inline CSS -->
    <p style="margin: 20px 15px 15px 15px; padding: 12px; background-color: #f8f9fa; border-left: 4px solid #d90429; font-size: 14px; color: #495057; border-radius: 4px;">
        <strong class="mr-1">Need to Buy or Rent?</strong>For assistance with any of these cars, please contact us at 
        <a class="ml-1" href="tel:+355 69 311 3209" style="color: #d90429; text-decoration: none; font-weight: 600;">+355 69 311 3209</a>
        or email us at 
        <a class="ml-1" href="mailto:autobuba2025@gmail.com " style="color: #d90429; text-decoration: none; font-weight: 600;">autobuba2025@gmail.com </a>
    </p>
</div>

    <!-- Add these AFTER jQuery but BEFORE other scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.7.1/nouislider.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Complete Cart JavaScript - Replace your existing cart JS with this

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
    
    document.querySelectorAll('.add-to-cart-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const vehicleId = this.dataset.id || '';
            const vehicleName = this.dataset.name || 'Unknown Vehicle';
            const vehiclePrice = this.dataset.price || '0';
            const vehicleImg = this.dataset.img || 'img/cars/default.jpg';
            const cartType = this.dataset.type || 'purchase';
            
            if (!vehicleId || !vehicleName || !vehiclePrice) {
                showToast('error', 'Missing vehicle information!');
                return;
            }
            
            const existingItem = cart.find(item => item.id === vehicleId);
            
            if (existingItem) {
                showToast('error', 'Item is already in cart!');
                return;
            }
            
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
            this.disabled = true;
            
            const cartItem = {
                id: vehicleId,
                name: vehicleName,
                price: parseFloat(vehiclePrice) || 0,
                img: vehicleImg,
                type: cartType
            };
            
            cart.push(cartItem);
            localStorage.setItem('autobuba_cart', JSON.stringify(cart));
            
            updateCartCounter();
            showToast('success', 'Item added to cart!');
            
            this.innerHTML = '<i class="fa fa-check"></i> Added';
            this.classList.remove('btn-outline-danger');
            this.classList.add('btn-success');
        });
    });
    
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
    const cartOverlay = document.getElementById('cartOverlay');
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
    if (cartOverlay) {
        cartOverlay.style.display = 'block';
    }
    
    setTimeout(() => {
        cartSidebar.classList.add('show');
        if (cartOverlay) {
            cartOverlay.classList.add('show');
        }
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
        
        const addButton = document.querySelector(`[data-id="${vehicleId}"]`);
        if (addButton) {
            addButton.innerHTML = '<i class="fa fa-cart-plus"></i>';
            addButton.classList.remove('btn-success');
            addButton.classList.add('btn-outline-danger');
            addButton.disabled = false;
        }
        
        showToast('success', 'Item removed from cart!');
    } else {
        showToast('error', 'Item not found in cart');
    }
}

function closeCart() {
    const cartSidebar = document.getElementById('cartCars');
    const cartOverlay = document.getElementById('cartOverlay');
    
    if (cartSidebar) {
        cartSidebar.classList.remove('show');
    }
    if (cartOverlay) {
        cartOverlay.classList.remove('show');
    }
    
    setTimeout(() => {
        if (cartSidebar) {
            cartSidebar.style.display = 'none';
        }
        if (cartOverlay) {
            cartOverlay.style.display = 'none';
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

<script>
// This script handles the price range slider functionality
document.addEventListener("DOMContentLoaded", () => {
  // Declare noUiSlider variable
  const noUiSlider = window.noUiSlider

  // Initialize rent price range slider
  const rentPriceRange = document.getElementById("rentPriceRange")
  if (rentPriceRange) {
    noUiSlider.create(rentPriceRange, {
      start: [0, 1000000],
      connect: true,
      step: 1000,
      range: {
        min: 0,
        max: 1000000,
      },
      format: {
        to: (value) => Math.round(value),
        from: (value) => Math.round(value),
      },
    })

    // Update hidden inputs and display when slider changes
    rentPriceRange.noUiSlider.on("update", (values, handle) => {
      document.getElementById("rentMinPrice").value = values[0]
      document.getElementById("rentMaxPrice").value = values[1]
      document.getElementById("amount").value = "$" + values[0].toLocaleString() + " - $" + values[1].toLocaleString()
    })
  }

  // Initialize buy price range slider
  const buyPriceRange = document.getElementById("buyPriceRange")
  if (buyPriceRange) {
    noUiSlider.create(buyPriceRange, {
      start: [0, 1000000],
      connect: true,
      step: 1000,
      range: {
        min: 0,
        max: 1000000,
      },
      format: {
        to: (value) => Math.round(value),
        from: (value) => Math.round(value),
      },
    })

    // Update hidden inputs and display when slider changes
    buyPriceRange.noUiSlider.on("update", (values, handle) => {
      document.getElementById("buyMinPrice").value = values[0]
      document.getElementById("buyMaxPrice").value =
        values[1]
      document.getElementById("buyAmount").value =
        "$" + values[0].toLocaleString() + " - $" + values[1].toLocaleString()
    })
  }

  // Handle form submissions
  const rentalForm = document.getElementById("rentalForm")
  if (rentalForm) {
    rentalForm.addEventListener("submit", function (e) {
      e.preventDefault()
      const formData = new FormData(this)
      const searchParams = new URLSearchParams()

      for (const [key, value] of formData.entries()) {
        searchParams.append(key, value)
      }

      // Fetch search results
      fetch("search-results.php?" + searchParams.toString())
        .then((response) => response.text())
        .then((html) => {
          // Display results in modal
          document.querySelector(".modal-body .row").innerHTML = html
          window.$("#searchModal").modal("show")
        })
        .catch((error) => console.error("Error:", error))
    })
  }

  const buyForm = document.getElementById("buyForm")
  if (buyForm) {
    buyForm.addEventListener("submit", function (e) {
      e.preventDefault()
      const formData = new FormData(this)
      const searchParams = new URLSearchParams()

      for (const [key, value] of formData.entries()) {
        searchParams.append(key, value)
      }

      // Fetch search results
      fetch("search-results.php?" + searchParams.toString())
        .then((response) => response.text())
        .then((html) => {
          // Display results in modal
          document.querySelector(".modal-body .row").innerHTML = html
          window.$("#searchModal").modal("show")
        })
        .catch((error) => console.error("Error:", error))
    })
  }

  // Handle brand selection to load models
  const rentBrand = document.getElementById("rentBrand")
  const rentModel = document.getElementById("rentModel")

  if (rentBrand && rentModel) {
    rentBrand.addEventListener("change", function () {
      const brand = this.value
      if (brand) {
        // Fetch models for the selected brand
        fetch("get_models.php?brand=" + encodeURIComponent(brand))
          .then((response) => response.json())
          .then((models) => {
            // Clear current options
            rentModel.innerHTML = '<option value="">Select Model</option>'

            // Add new options
            models.forEach((model) => {
              const option = document.createElement("option")
              option.value = model
              option.textContent = model
              rentModel.appendChild(option)
            })
          })
          .catch((error) => console.error("Error:", error))
      } else {
        // Reset models dropdown if no brand is selected
        rentModel.innerHTML = '<option value="">Select Model</option>'
      }
    })
  }

  const buyBrand = document.getElementById("buyBrand")
  const buyModel = document.getElementById("buyModel")

  if (buyBrand && buyModel) {
    buyBrand.addEventListener("change", function () {
      const brand = this.value
      if (brand) {
        // Fetch models for the selected brand
        fetch("get_models.php?brand=" + encodeURIComponent(brand))
          .then((response) => response.json())
          .then((models) => {
            // Clear current options
            buyModel.innerHTML = '<option value="">Select Model</option>'

            // Add new options
            models.forEach((model) => {
              const option = document.createElement("option")
              option.value = model
              option.textContent = model
              buyModel.appendChild(option)
            })
          })
          .catch((error) => console.error("Error:", error))
      } else {
        // Reset models dropdown if no brand is selected
        buyModel.innerHTML = '<option value="">Select Model</option>'
      }
    })
  }
})
</script>

    <script>
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

    <script>
        // Phone button functionality
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

<script>
    // Car filter functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Get all filter buttons and car items
        const filterButtons = document.querySelectorAll('.filter__controls li');
        const carItems = document.querySelectorAll('.car-filter .mix');

        // Add click event to each filter button
        filterButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all buttons
                filterButtons.forEach(btn => {
                    btn.classList.remove('active');
                });
                
                // Add active class to clicked button
                this.classList.add('active');
                
                // Get filter value
                const filterValue = this.getAttribute('data-filter');
                
                // Show/hide cars based on filter
                carItems.forEach(item => {
                    if (filterValue === 'all') {
                        item.style.display = 'block';
                    } else if (item.classList.contains(filterValue)) {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        });
    });
</script>

<script>
function handleFormSubmit(formType) {
    const form = document.getElementById(formType === 'rent' ? 'rentalForm' : 'buyForm');
    const formData = new FormData(form);
    
    // Get price range values
    const priceRange = formType === 'rent' ? 
        document.querySelector('.price-range').noUiSlider.get() :
        document.querySelector('.buy-price-range').noUiSlider.get();
        
    formData.append('min_price', Math.round(priceRange[0]));
    formData.append('max_price', Math.round(priceRange[1]));

    // Submit via AJAX
    fetch(window.location.href, {
        method: 'GET',
        body: new URLSearchParams(formData)
    })
    .then(response => response.text())
    .then(html => {
        // Update search results
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const newResults = doc.querySelector('.car-filter').innerHTML;
        
        document.querySelector('.car-filter').innerHTML = newResults;
        $('#searchModal').modal('show');
    })
    .catch(error => console.error('Error:', error));

    return false;
}
</script>
<!-- Js Plugins -->
<script src="js/jquery-3.3.1.min.js"></script>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.7.0/nouislider.min.js"></script>

<script>
$(document).ready(function() {
  $('#searchModal').modal({ show: false });
});
</script>

<script>
// Translation data
const translations = {
  en: {
    // Header
    Cars: "Cars",
    Blog: "Blog",
    "Car Details": "Car Details",
    About: "About",
    Work: "Work",
    Cart: "Cart",
    Report: "Report",
    "Log In": "Log In",

    // Hero Section
    "IT ALL STARTS WITH A DREAM": "IT ALL STARTS WITH A DREAM",
    "All of those cars were once just a dream in somebody's head. - Peter Gabriel":
      "All of those cars were once just a dream in somebody's head. - Peter Gabriel",
    "Test Drive": "Test Drive",
    "Learn More": "Learn More",
    "Car Rental": "Car Rental",
    "Buy Car": "Buy Car",
    "Rent Your Dream Car": "Rent Your Dream Car",
    "Select Year": "Select Year",
    "Select Brand": "Select Brand",
    "Select Model": "Select Model",
    "Select Mileage": "Select Mileage",
    "Price Range:": "Price Range:",
    Search: "Search",
    "Buy Your Dream Car": "Buy Your Dream Car",
    "Search Results": "Search Results",
    "No results found. Try different search criteria.": "No results found. Try different search criteria.",
    Close: "Close",
    "View All Cars": "View All Cars",

    // Services Section
    "Our Services": "Our Services",
    "What We Offer": "What We Offer",
    "Well-maintained, clean, and sanitized cars, excellent customer service and fast & hassle-free rental process":
      "Well-maintained, clean, and sanitized cars, excellent customer service and fast & hassle-free rental process",
    "Renting Cars": "Renting Cars",
    "Need a reliable ride? We offer a seamless car rental experience with a wide range of vehicles to fit your needs.":
      "Need a reliable ride? We offer a seamless car rental experience with a wide range of vehicles to fit your needs.",
    "Buying Cars": "Buying Cars",
    "Looking for your next car? We offer a hassle-free buying experience with a selection of high-quality vehicles.":
      "Looking for your next car? We offer a hassle-free buying experience with a selection of high-quality vehicles.",
    "Quality Assurance": "Quality Assurance",
    "Well-maintained, clean, and reliable vehicles, ensuring a safe and comfortable drive every time.":
      "Well-maintained, clean, and reliable vehicles, ensuring a safe and comfortable drive every time.",

    // Feature Section
    "Our Feature": "Our Feature",
    "We Are a Trusted Name In Auto": "We Are a Trusted Name In Auto",
    "As a trusted name in the automotive industry, we don't just meet expectations—we aim to exceed them. Our commitment to innovation, professionalism, and building lasting relationships has solidified our place as a leader in the field.":
      "As a trusted name in the automotive industry, we don't just meet expectations—we aim to exceed them. Our commitment to innovation, professionalism, and building lasting relationships has solidified our place as a leader in the field.",
    "Our Reviews": "Our Reviews",
    Engine: "Engine",
    Turbo: "Turbo",
    Cooling: "Cooling",
    Suspension: "Suspension",
    Electrical: "Electrical",
    Brakes: "Brakes",

    // Car Listing Section
    "Our Car": "Our Car",
    "Best Vehicle Offers": "Best Vehicle Offers",
    "Most Researched": "Most Researched",
    "Latest on sale": "Latest on sale",
    "Discount Vehicles": "Discount Vehicles",
    "For Rent": "For Rent",
    "For Sale": "For Sale",
    "View More": "View More",
    mi: "mi",
    hp: "hp",
    Auto: "Auto",
    "/mo": "/mo",
    "No vehicles available": "No vehicles available",

    // Choose Us Section
    "Why People Choose Us": "Why People Choose Us",
    "People choose us because we deliver unmatched quality, personalized service, and reliability that you can always count on.":
      "People choose us because we deliver unmatched quality, personalized service, and reliability that you can always count on.",
    "Unmatched quality and attention to detail.": "Unmatched quality and attention to detail.",
    "Exceptional customer service tailored to your needs.": "Exceptional customer service tailored to your needs.",
    "Affordable solutions without compromising reliability.": "Affordable solutions without compromising reliability.",
    "A proven track record of trust and excellence.": "A proven track record of trust and excellence.",
    "About Us": "About Us",

    // Footer
    Navigation: "Navigation",
    "Contact Us": "Contact Us",
    Learn: "Learn",
    "Why Us?": "Why Us?",
    "Who are we?": "Who are we?",
    Hatchback: "Hatchback",
    Sedan: "Sedan",
    SUV: "SUV",
    Copyright: "© Copyright 2025 QUOLYTECH. All Rights Reserved.",

    // Phone Modal
    "Contact Information": "Contact Information",
    "Please call us at:": "Please call us at:",
    Copy: "Copy",
    "Copied to clipboard!": "Copied to clipboard!",

    // Shopping Cart
    "Your Cart": "Your Cart",
    "Your cart is empty": "Your cart is empty",
    "Add some cars to get started!": "Add some cars to get started!",

    // Loading Screen
    "km/h": "km/h",
    "Home - AUTOBUBA": "Home - AUTOBUBA",
  },
  sq: {
    // Header
    Cars: "Makinat",
    Blog: "Blog",
    "Car Details": "Detajet e Makinës",
    About: "Rreth Nesh",
    Work: "Puna",
    Cart: "Shporta",
    Report: "Raporto",
    "Log In": "Hyr",

    // Hero Section
    "IT ALL STARTS WITH A DREAM": "E GJITHA FILLON ME NJË ËNDËRR",
    "All of those cars were once just a dream in somebody's head. - Peter Gabriel":
      "Të gjitha ato makina ishin dikur thjesht një ëndërr në kokën e dikujt. - Peter Gabriel",
    "Test Drive": "Test Drive",
    "Learn More": "Mësoni Më Shumë",
    "Car Rental": "Qiraja e Makinave",
    "Buy Car": "Blini Makinë",
    "Rent Your Dream Car": "Merrni Me Qira Makinën Tuaj të Ëndrrave",
    "Select Year": "Zgjidhni Vitin",
    "Select Brand": "Zgjidhni Markën",
    "Select Model": "Zgjidhni Modelin",
    "Select Mileage": "Zgjidhni Kilometrazhin",
    "Price Range:": "Çmimet:",
    Search: "Kërko",
    "Buy Your Dream Car": "Blini Makinën Tuaj të Ëndrrave",
    "Search Results": "Rezultatet e Kërkimit",
    "No results found. Try different search criteria.": "Nuk u gjetën rezultate. Provoni kritere të ndryshme kërkimi.",
    Close: "Mbyll",
    "View All Cars": "Shiko Të Gjitha Makinat",

    // Services Section
    "Our Services": "Shërbimet Tona",
    "What We Offer": "Çfarë Ofrojmë",
    "Well-maintained, clean, and sanitized cars, excellent customer service and fast & hassle-free rental process":
      "Makina të mirëmbajtura, të pastra dhe të dezinfektuara, shërbim të shkëlqyer të klientit dhe proces të shpejtë dhe të lehtë të qirasë",
    "Renting Cars": "Qira e Makinave",
    "Need a reliable ride? We offer a seamless car rental experience with a wide range of vehicles to fit your needs.":
      "Keni nevojë për një udhëtim të besueshëm? Ne ofrojmë një përvojë të qetë të qirasë së makinave me një gamë të gjerë automjetesh që përshtaten me nevojat tuaja.",
    "Buying Cars": "Blerja e Makinave",
    "Looking for your next car? We offer a hassle-free buying experience with a selection of high-quality vehicles.":
      "Po kërkoni makinën tuaj të radhës? Ne ofrojmë një përvojë blerjeje të lehtë me një përzgjedhje automjetesh me cilësi të lartë.",
    "Quality Assurance": "Siguria e Cilësisë",
    "Well-maintained, clean, and reliable vehicles, ensuring a safe and comfortable drive every time.":
      "Automjete të mirëmbajtura, të pastra dhe të besueshme, duke siguruar një vozitje të sigurt dhe të rehatshme çdo herë.",

    // Feature Section
    "Our Feature": "Karakteristika Jonë",
    "We Are a Trusted Name In Auto": "Ne Jemi Një Emër i Besuar Në Automjete",
    "As a trusted name in the automotive industry, we don't just meet expectations—we aim to exceed them. Our commitment to innovation, professionalism, and building lasting relationships has solidified our place as a leader in the field.":
      "Si një emër i besuar në industrinë automobilistike, ne jo vetëm që plotësojmë pritshmëritë - synojmë t'i tejkalojmë ato. Angazhimi ynë për inovacion, profesionalizëm dhe ndërtimin e marrëdhënieve të qëndrueshme ka forcuar vendin tonë si lider në këtë fushë.",
    "Our Reviews": "Vlerësimet Tona",
    Engine: "Motor",
    Turbo: "Turbo",
    Cooling: "Ftohje",
    Suspension: "Pezullim",
    Electrical: "Elektrike",
    Brakes: "Frena",

    // Car Listing Section
    "Our Car": "Makinat Tona",
    "Best Vehicle Offers": "Ofertat Më të Mira të Automjeteve",
    "Most Researched": "Më të Kërkuarat",
    "Latest on sale": "Më të Fundit në shitje",
    "Discount Vehicles": "Automjete me Zbritje",
    "For Rent": "Për Qira",
    "For Sale": "Për Shitje",
    "View More": "Shiko Më Shumë",
    mi: "km",
    hp: "hp",
    Auto: "Automatik",
    "/mo": "/muaj",
    "No vehicles available": "Nuk ka automjete të disponueshme",

    // Choose Us Section
    "Why People Choose Us": "Pse Njerëzit Na Zgjedhin Ne",
    "People choose us because we deliver unmatched quality, personalized service, and reliability that you can always count on.":
      "Njerëzit na zgjedhin sepse ne ofrojmë cilësi të paarsyeshme, shërbim të personalizuar dhe besueshmëri që gjithmonë mund të mbështeteni.",
    "Unmatched quality and attention to detail.": "Cilësi e paarsyeshme dhe vëmendje ndaj detajeve.",
    "Exceptional customer service tailored to your needs.":
      "Shërbim të jashtëzakonshëm të klientit të përshtatur sipas nevojave tuaja.",
    "Affordable solutions without compromising reliability.":
      "Zgjidhje të përballueshme pa kompromis për besueshmërinë.",
    "A proven track record of trust and excellence.": "Një rekord i provuar i besimit dhe i ekselencës.",
    "About Us": "Rreth Nesh",

    // Footer
    Navigation: "Navigimi",
    "Contact Us": "Na Kontaktoni",
    Learn: "Mësoni",
    "Why Us?": "Pse Ne?",
    "Who are we?": "Kush jemi ne?",
    Hatchback: "Hatchback",
    Sedan: "Sedan",
    SUV: "SUV",
    Copyright: "© E drejta e autorit 2025 QUOLYTECH. Të gjitha të drejtat e rezervuara.",

    // Phone Modal
    "Contact Information": "Informacioni i Kontaktit",
    "Please call us at:": "Ju lutemi na telefononi në:",
    Copy: "Kopjo",
    "Copied to clipboard!": "U kopjua në clipboard!",

    // Shopping Cart
    "Your Cart": "Shporta Juaj",
    "Your cart is empty": "Shporta juaj është bosh",
    "Add some cars to get started!": "Shtoni disa makina për të filluar!",

    // Loading Screen
    "km/h": "km/h",
    "Home - AUTOBUBA": "Kryefaqja - AUTOBUBA",
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
    if (currentTranslations["Home - AUTOBUBA"]) {
      document.title = currentTranslations["Home - AUTOBUBA"]
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
    
    /* Enhanced Language Dropdown Styles */
    .language-dropdown {
        position: relative;
    }
    
    .language-dropdown .dropdown-toggle {
        background: linear-gradient(135deg, #fff, #f8f9fa);
        border: 2px solid #e9ecef;
        border-radius: 8px;
        padding: 8px 12px;
        min-width: 50px;
        height: 42px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .language-dropdown .dropdown-toggle:hover {
        border-color: #d90429;
        box-shadow: 0 4px 12px rgba(217, 4, 41, 0.2);
        transform: translateY(-1px);
    }
    
    .language-dropdown .dropdown-toggle:focus {
        border-color: #d90429;
        box-shadow: 0 0 0 3px rgba(217, 4, 41, 0.1);
    }
    
    .language-dropdown .dropdown-toggle::after {
        display: none;
    }
    
    .language-dropdown .dropdown-menu {
        border: none;
        border-radius: 12px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        padding: 8px;
        min-width: 120px;
        margin-top: 8px;
        background: white;
        backdrop-filter: blur(10px);
    }
    
    .language-dropdown .dropdown-item {
        border-radius: 8px;
        padding: 10px 16px;
        margin: 2px 0;
        transition: all 0.2s ease;
        border: none;
        background: transparent;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 40px;
    }
    
    .language-dropdown .dropdown-item:hover {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        transform: translateX(3px);
        color: #d90429;
    }
    
    .language-dropdown .dropdown-item.active {
        background: linear-gradient(135deg, #d90429, #c82333);
        color: white;
        box-shadow: 0 2px 8px rgba(217, 4, 41, 0.3);
    }
    
    .language-dropdown .dropdown-item.active:hover {
        background: linear-gradient(135deg, #c82333, #b21e2f);
        transform: translateX(0);
    }
    
    .language-dropdown .flag-icon {
        font-size: 1.2em;
        margin: 0;
        width: 24px;
        height: 18px;
        border-radius: 3px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    }
    
    /* Animation for dropdown appearance */
    .language-dropdown .dropdown-menu.show {
        animation: dropdownSlideIn 0.2s ease;
    }
    
    @keyframes dropdownSlideIn {
        from {
            opacity: 0;
            transform: translateY(-10px) scale(0.95);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }
    
    /* Mobile responsive */
    @media (max-width: 768px) {
        .language-dropdown .dropdown-menu {
            min-width: 100px;
            margin-right: 10px;
        }
        
        .language-dropdown .dropdown-toggle {
            min-width: 45px;
            height: 38px;
            padding: 6px 10px;
        }
        
        .language-dropdown .flag-icon {
            width: 20px;
            height: 15px;
        }
    }
`
document.head.appendChild(style)

</script>

</body>
</html>
